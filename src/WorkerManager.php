<?php
namespace GMO\Beanstalk;

use GMO\Common\Collection;
use GMO\Common\String;
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
			echo "php $filename restart|start|stop|stats|beanstalkd\n";
			echo "              restart|start|stop [worker]\n";
			echo "beanstalkd: Starts beanstalkd if not running (also starts workers if beanstalkd stopped)\n";
			echo "\n\n";
			exit(1);
		}

		$args = array_merge($args, array("", ""));
		$workerName = $args[2];

		switch ( $args[1] ) {
			case "beanstalkd":
				if ( !$this->isBeanstalkdRunning() ) {
					$this->startBeanstalkd();
					$this->startWorkers();
				}
				break;
			case "restart":
				$this->restartWorkers($workerName);
				break;
			case "stop":
				$this->stopWorkers($workerName);
				break;
			case "start":
				$this->startWorkers($workerName);
				break;
			case "stats":
				$this->log->info(print_r($this->getStats(), true));
				break;
			default:
				help( $filename );
		}

	}

	/**
	 * Restarts all beanstalk workers
	 * @param string $workerName [optional] worker name filter
	 */
	public function restartWorkers($workerName = null) {
		$this->stopWorkers($workerName);
		$this->startWorkers($workerName);
	}

	/**
	 * Spawns workers of each type up to the number of
	 * workers specified in each worker class.
	 * @param string $workerName [optional] worker name filter
	 */
	public function startWorkers($workerName = null) {
		$this->log->info( "Starting workers..." );
		# get workers
		$workers = $this->getWorkers( $workerName );

		# get currently running workers
		$currentWorkers = $this->getRunningWorkers();

		/**
		 * loop through workers
		 * @var AbstractWorker $class
		 */
		foreach ( $workers as $worker => $class ) {
			# get the number of currently running workers of this type
			$currentNumber = Collection::get($currentWorkers, $worker, 0);

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
	 * Get an array of workers that have the
	 * AbstractWorker as their parent class
	 * @param string $workerName [optional] worker name filter
	 * @return array key: class name, value: class instance
	 */
	public function getWorkers($workerName = null) {
		$files = glob( $this->workerDir . "*.php" );
		$workers = array();
		foreach ( $files as $file ) {
			# parse classes in file
			$classNames = $this->getPhpClasses( $file );
			# only use the first class
			$classNameWithNamespace = $classNames[0];
			# remove class name without namespace
			$className = String::splitLast($classNameWithNamespace, "\\");

			if ($workerName && !String::containsInsensitive($className, $workerName)) {
				continue;
			}

			$cls = new \ReflectionClass($classNameWithNamespace);
			if ($cls->isInstantiable() && $cls->isSubclassOf('\GMO\Beanstalk\AbstractWorker')) {
				$workers[$className] = $cls->newInstance();
			}
		}
		return $workers;
	}

	/**
	 * Get a key/value array of currently running workers
	 * key: worker name, value: number of workers
	 * @return array
	 */
	public function getRunningWorkers() {
		# get beanstalk processes
		$processes = $this->listProcesses( $this->workerDir );

		# parse processes into workers array
		$workers = array();
		foreach ( $processes as $process ) {
			$matches = array();
			$preg_worker_dir = str_replace('/', '\/', $this->workerDir);
			if(!preg_match('/' . $preg_worker_dir . '(.*?)[.]php/', $process, $matches)) {
				continue;
			}
			$worker = $matches[1];
			$workers = Collection::increment($workers, $worker);
		}

		return $workers;
	}

	/**
	 * Returns an array containing: WorkerName => # Running / # Total
	 * @return array
	 */
	public function getStats() {
		$stats = array();

		$workers = $this->getWorkers();
		$runningWorkers = $this->getRunningWorkers();
		/** @var AbstractWorker $class */
		foreach ( $workers as $worker => $class ) {
			$currentNum = Collection::get($runningWorkers, $worker, 0);
			$stats[$worker] = $currentNum . "/" . $class->getNumberOfWorkers();
		}

		return $stats;
	}

	/**
	 * Stops all beanstalk workers
	 * @param string $workerName [optional] worker name filter
	 */
	public function stopWorkers($workerName = null) {
		# get beanstalk processes
		$workers = array_keys($this->getWorkers($workerName));
		$processes = array();
		foreach ($workers as $worker) {
			$processes = array_merge($processes, $this->listProcesses( $this->workerDir . $worker ));
		}
		$processes = array_map(function($process) {
				# parse process id
				$parts = preg_split( "/[\\s]+/", $process );
				return $parts[1];
			}, $processes);

		$this->log->info( "Stopping workers: " . count( $processes ) );

		// Send signal to workers to stop
		foreach ($processes as $process) {
			$this->log->debug("Sending terminate signal to: $process");
			posix_kill($process, SIGTERM);
		}
		// Wait for them to exit
		foreach ($processes as $process) {
			$this->log->debug("Waiting for: $process...");
			while($this->isProcessRunning($process)) {
				usleep(200 * 1000); // 200 milliseconds
			}
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
	 * @TODO In 2.0 make logger optional
	 */
	function __construct( $workerDir, LoggerInterface $logger, $host, $port ) {
		$this->workerDir = realpath( $workerDir ) . "/";

		$this->log = $logger;
		$this->host = $host;
		$this->port = $port;
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
				 * @var AbstractWorker $workerInstance
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
		$grep = preg_replace('/ /', '\ ', $grep);

		# get list of workers
		$lines = array();
		$this->execute( "ps aux | grep -v grep | grep " . $grep, $lines );
		return $lines;
	}

	/**
	 * Checks if pid is running
	 * @param $pid
	 * @return bool
	 */
	public function isProcessRunning($pid) {
		$this->execute("ps $pid", $lines, $exitCode);
		return $exitCode === 0;
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
