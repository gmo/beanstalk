<?php
namespace GMO\Beanstalk;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * WorkerManager controls beanstalk workers.
 *
 * @example restart workers in code
 *          $manager = new WorkerManager( $workerDir, $logger, $host, $port );
 *          $manager->restartWorkers();
 *
 * @example workers.php file for command line usage
 *          $manager = new WorkerManager( $workerDir, $logger, $host, $port );
 *          $manager->runCommand($argv);
 *
 * @package GMO\Beanstalk
 *
 * @since 1.0.0
 */
class WorkerManager implements LoggerAwareInterface {

	/**
	 * Use this method when running from command-line.
	 * Calls a method based on args, or displays usage.
	 * @param array $args command-line arguments
	 */
	public function runCommand( $args ) {
		$filename = basename( $args[0] );

		function help( $filename ) {
			echo "php $filename [restart|start|stop|beanstalkd]\n";
			echo "beanstalkd: Starts beanstalkd if not running (also starts workers if beanstalkd stopped)\n";
			exit(1);
		}

		if ( count( $args ) == 1 ) {
			help( $filename );
		}

		switch ( $args[1] ) {
			case "beanstalkd":
				if ( !$this->isBeanstalkdRunning() ) {
					$this->startBeanstalkd();
					$this->startWorkers();
				}
				break;
			case "restart":
				$this->restartWorkers();
				break;
			case "stop":
				$this->stopWorkers();
				break;
			case "start":
				$this->startWorkers();
				break;
			default:
				help( $filename );
		}

	}

	/**
	 * Restarts all beanstalk workers
	 */
	public function restartWorkers() {
		$this->stopWorkers();
		$this->startWorkers();
	}

	/**
	 * Spawns workers of each type up to the number of
	 * workers specified in each worker class.
	 */
	public function startWorkers() {
		$this->log->info( "Starting workers..." );
		# get workers
		$workers = $this->getWorkers( $this->workerDir );

		# get currently running workers
		$currentWorkers = $this->listWorkers();

		/**
		 * loop through workers
		 * @param AbstractWorker $class
		 */
		foreach ( $workers as $worker => $class ) {
			# get the number of currently running workers of this type
			if ( !isset($currentWorkers[$worker]) ) {
				$currentNumber = 0;
			} else {
				$currentNumber = $currentWorkers[$worker];
			}

			# set number of new workers to spawn from this difference
			$workersToSpawn = $class->getNumberOfWorkers() - $currentNumber;

			# spawn new workers
			for ( $i = 0; $i < $workersToSpawn; $i++ ) {
				$this->startWorker( $worker );
			}
		}
	}

	/**
	 * Spawn a new worker given the class name
	 * @param string $worker class name
	 */
	public function startWorker( $worker ) {
		if ( !file_exists( $this->workerDir . $worker . ".php" ) ) {
			$this->log->error( "Worker: $worker doesn't exist" );
			return;
		}
		$this->log->info( "Starting worker: " . $worker );
		$this->execute(
		     "nohup php {$this->workerDir}{$worker}.php --run " .
		     "$this->host $this->port" .
		     //TODO: Use actual logger not redirection
		     " >> /var/log/gmo/beanstalkd/$worker.log 2>&1 &"
		);
	}

	/**
	 * Should not be called directly, only from the worker file.
	 * Relies on the parameters in startWorker.
	 */
	public static function runWorker() {
		global $argv;
		if ( in_array( "--run", $argv ) ) {
			try {
				# get first class in called file
				$classes = self::getPhpClasses( $argv[0] );
				/**
				 * create instance
				 * @param AbstractWorker $workerInstance
				 */
				$class = $classes[0];
				$workerInstance = new $class;
				# start it
				$workerInstance->run( $argv[2], $argv[3] );
			} catch ( \Exception $ex ) {
				// TODO: Log the exception
			}
		}
	}

	/**
	 * Get an array of workers that have the
	 * AbstractWorker as their parent class
	 * @return array key: class name, value: class instance
	 */
	public function getWorkers() {
		$files = glob( $this->workerDir . "*.php" );
		$workers = array();
		foreach ( $files as $file ) {
			# parse classes in file
			$classNames = $this->getPhpClasses( $file );
			# only use the first class
			$classNameWithNamespace = $classNames[0];
			# remove class name without namespace
			$parts = explode( "\\", $classNameWithNamespace );
			$className = $parts[count( $parts ) - 1];

			if ( $className == "AbstractWorker" ) {
				continue;
			}

			# Add to workers if it's a subclass of AbstractWorker
			$class = new $classNameWithNamespace;
			if ( is_subclass_of( $class, "\\GMO\\Beanstalk\\AbstractWorker" ) ) {
				$workers[$className] = $class;
			}
		}
		return $workers;
	}

	/**
	 * Get a key/value array of currently running workers
	 * key: worker name, value: number of workers
	 * @return array
	 */
	public function listWorkers() {
		# get beanstalk processes
		$processes = $this->listProcesses( $this->workerDir );

		# parse processes into workers array
		$workers = array();
		foreach ( $processes as $process ) {
			# remove ending run command
			$process = trim( str_replace( "--run", "", $process ) );
			# remove beginning junk
			$process = substr( $process, strpos( $process, $this->workerDir ) );
			# remove worker directory
			$process = str_replace( $this->workerDir, "", $process );
			# remove php extension
			$process = str_replace( ".php", "", $process );
			$worker = $process;

			# increment worker counter
			if ( !isset($workers[$worker]) ) {
				$workers[$worker] = 0;
			}
			$workers[$worker]++;
		}

		return $workers;
	}

	/**
	 * Stops all beanstalk workers
	 */
	public function stopWorkers() {
		# get beanstalk processes
		$processes = $this->listProcesses( $this->workerDir );

		$this->log->info( "Stopping workers: " . count( $processes ) );

		# kill each process
		foreach ( $processes as $process ) {
			# parse process id
			$parts = preg_split( "/[\\s]+/", $process );
			$processId = $parts[1];

			$this->execute( "kill $processId" );
		}
	}

	/**
	 * Checks if beanstalkd is running
	 * @return bool
	 */
	public function isBeanstalkdRunning() {
		$processes = $this->listProcesses( "bin/beanstalkd" );
		return $processes > 0;
	}

	/**
	 * Starts beanstalkd
	 */
	public function startBeanstalkd() {
		$this->log->info( "Starting beanstalkd" );
		$this->execute( "/etc/init.d/beanstalkd start" );
	}

	/**
	 * Sets a logger instance on the object
	 * @param LoggerInterface $logger
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->log = $logger;
	}

	/**
	 * @param string          $workerDir Directory containing workers
	 * @param LoggerInterface $logger
	 * @param string          $host      Beanstalkd host
	 * @param int             $port      Beanstalkd port
	 */
	function __construct( $workerDir, LoggerInterface $logger, $host, $port ) {
		$workerDir = realpath( $workerDir );
		# add ending slash if needed
		if ( substr( $workerDir, -1 ) != "/" ) {
			$workerDir .= "/";
		}
		$this->workerDir = $workerDir;

		$this->log = $logger;
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * Wraps the exec() function. Used for testing.
	 * @param       $command
	 * @param array $output
	 * @param null  $return_var
	 */
	protected function execute( $command, array &$output = null, &$return_var = null ) {
		exec( $command, $output, $return_var );
	}

	/**
	 * Lists processes matching a search term
	 * @param $grep
	 * @return array
	 */
	protected function listProcesses( $grep ) {
		# get list of workers
		$lines = array();
		$this->execute( "ps aux | grep " . $grep, $lines );

		$processes = array();
		foreach ( $lines as $line ) {
			# Remove our grep call
			if ( strpos( $line, "grep" ) > 0 ) {
				continue;
			}
			$processes[] = $line;
		}

		return $processes;
	}

	/**
	 * Get class names from file
	 * @param string $file
	 * @return array
	 */
	private static function getPhpClasses( $file ) {
		$classes = array();
		$phpCode = file_get_contents( $file );
		$tokens = token_get_all( $phpCode );
		$namespace = self::getNamespaceFromTokens( $tokens );

		for ( $i = 0; $i < count( $tokens ); $i++ ) {
			# append fully qualified class name to list
			if ( $tokens[$i][0] == T_CLASS && $tokens[$i + 1][0] == T_WHITESPACE && $tokens[$i + 2][0] == T_STRING
			) {
				$className = $tokens[$i + 2][1];
				$classNameWithNamespace = $namespace . "\\" . $className;
				$classes[] = $classNameWithNamespace;
			}
		}
		return $classes;
	}

	/**
	 * Gets namespace from tokenized php file
	 * @param $tokens
	 * @return string
	 */
	private static function getNamespaceFromTokens( $tokens ) {
		$namespace = "\\";
		$inNamespaceToken = false;
		for ( $i = 0; $i < count( $tokens ); $i++ ) {
			if ( $tokens[$i] === ";" ) {
				# end of namespace declaration
				# or no namespace
				break;
			} elseif ( $tokens[$i][0] == T_WHITESPACE ) {
				# skip whitespace tokens
				continue;
			} elseif ( $inNamespaceToken && ($tokens[$i][0] == T_STRING || $tokens[$i][0] == T_NS_SEPARATOR) ) {
				# append next part of namespace
				$namespace .= $tokens[$i][1];
			} elseif ( $tokens[$i][0] == T_NAMESPACE ) {
				# start of namespace declaration
				$inNamespaceToken = true;
			}
		}
		return $namespace;
	}

	/** @var string Directory containing workers */
	protected $workerDir;

	/** @var LoggerInterface */
	private $log;

	/** @var string */
	private $host;
	/** @var int */
	private $port;
}
