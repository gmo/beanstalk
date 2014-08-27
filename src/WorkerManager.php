<?php
namespace GMO\Beanstalk;

use GMO\Common\Collection;
use GMO\Common\String;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
			echo "php $filename restart|start|stop|stats [worker ...]\n";
			echo "\n";
			exit(1);
		}

		$filter = array_slice($args, 2);

		switch (Collection::get($args, 1)) {
			case "beanstalkd":
				if ( !$this->isBeanstalkdRunning() ) {
					$this->startBeanstalkd();
					$this->startWorkers();
				}
				break;
			case "restart":
				$this->restartWorkers($filter);
				break;
			case "stop":
				$this->stopWorkers($filter);
				break;
			case "start":
				$this->startWorkers($filter);
				break;
			case "stats":
				$this->log->info(print_r($this->getStats($filter), true));
				break;
			default:
				help( $filename );
		}

	}

	/**
	 * Restarts all beanstalk workers
	 * @param null|string|array $filter [optional] worker(s) filter
	 */
	public function restartWorkers($filter = null) {
		$this->stopWorkers($filter);
		$this->startWorkers($filter);
	}

	/**
	 * Spawns workers of each type up to the number of
	 * workers specified in each worker class.
	 * @param null|string|array $filter [optional] worker(s) filter
	 */
	public function startWorkers($filter = null) {
		$this->log->info( "Starting workers..." );
		# get workers
		$workers = $this->getWorkers( $filter );

		foreach ($workers as $worker) {
			$workersToSpawn = $worker->getTotal() - $worker->getNumRunning();

			if ($workersToSpawn > 0) {
				$this->log->info("Starting $workersToSpawn workers: " . $worker->getName());
			}
			# spawn new workers
			for ( $i = 0; $i < $workersToSpawn; $i++ ) {
				$this->spawnWorker($worker);
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

		$classes = $this->getPhpClasses($this->workerDir . $worker . '.php');
		$worker = new WorkerInfo($classes[0]);

		$this->log->info("Starting worker: " . $worker->getName());
		$this->spawnWorker($worker);
	}

	/**
	 * Get an array of workers that have the
	 * AbstractWorker as their parent class
	 * @param null|string|array $filter [optional] worker(s) filter
	 * @return WorkerInfo[]
	 */
	public function getWorkers($filter = null) {
		$files = glob( $this->workerDir . "*.php" );
		/** @var WorkerInfo[] $workers */
		$workers = array();
		foreach ( $files as $file ) {
			# parse classes in file and use first
			$classNames = $this->getPhpClasses( $file );
			$workerInfo = new WorkerInfo($classNames[0]);

			if (!$this->filterWorkers($workerInfo->getName(), $filter)) {
				continue;
			}

			$cls = $workerInfo->getReflectionClass();
			if ($cls->isInstantiable() && $cls->isSubclassOf('\GMO\Beanstalk\AbstractWorker')) {
				$workers[$workerInfo->getFullyQualifiedName()] = $workerInfo;
			}
		}

		$this->execute(sprintf('ps ax -o pid,command | grep -v grep | grep "runner \"%s\""', $this->workerDir), $processes);
		foreach ( $processes as $process ) {
			if (!preg_match_all('/"[^"]+"|\S+/', $process, $matches)) {
				continue;
			}
			$parts = $matches[0];
			if (isset($workers[$parts[4]])) {
				$workers[$parts[4]]->addPid($parts[0]);
			}
		}

		return $workers;
	}

	/**
	 * Returns an array containing: WorkerName => # Running / # Total
	 * @param null|string|array $filter [optional] worker(s) filter
	 * @return array
	 */
	public function getStats($filter = null) {
		$stats = array();

		$workers = $this->getWorkers($filter);
		foreach ($workers as $worker) {
			$stats[$worker->getName()] = $worker->getNumRunning() . "/" . $worker->getTotal();
		}

		return $stats;
	}

	/**
	 * Stops all beanstalk workers
	 * @param null|string|array $filter [optional] worker(s) filter
	 */
	public function stopWorkers($filter = null) {
		$workers = $this->getWorkers($filter);
		foreach ($workers as $worker) {
			if (count($worker->getPids()) === 0) {
				continue;
			}
			$this->log->info( "Stopping workers: " . $worker->getName() );
			foreach ($worker->getPids() as $pid) {
				$this->log->debug(sprintf("Terminating: [%s] %s", $pid, $worker->getName()));
				posix_kill($pid, SIGTERM);
			}
		}
		foreach ($workers as $worker) {
			foreach ($worker->getPids() as $pid) {
				$this->log->debug(sprintf("Waiting for: [%s] %s...", $pid, $worker->getName()));
				$this->waitForProcess($pid);
				$worker->removePid($pid);
			}
		}
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	public function isBeanstalkdRunning() { return true; }

	/**
	 * @deprecated
	 */
	public function startBeanstalkd() { }

	/** @inheritdoc */
	public function setLogger( LoggerInterface $logger ) {
		$this->log = $logger;
	}

	private function spawnWorker(WorkerInfo $worker) {
		//TODO: Use actual logger not redirection
		$cmd = sprintf('%s "\"%s\"" "%s" %s %d >> /var/log/gmo/beanstalkd/%s.log 2>&1 &',
			'./runner',
			$this->workerDir,
			$worker->getFullyQualifiedName(),
			$this->host,
			$this->port,
			$worker->getName()
		);
		$cwd = getcwd();
		chdir(__DIR__ . '/../bin');
		$this->execute($cmd);
		chdir($cwd);
	}

	/**
	 * @param string          $workerDir Directory containing workers
	 * @param LoggerInterface $logger
	 * @param string          $host      Beanstalkd host
	 * @param int             $port      Beanstalkd port
	 */
	public function __construct($workerDir, LoggerInterface $logger = null, $host = 'localhost', $port = 11300) {
		$this->workerDir = realpath( $workerDir ) . "/";

		$this->setLogger($logger ?: new NullLogger());
		$this->host = $host;
		$this->port = $port;
	}

	/** @deprecated */
	public static function runWorker() { }

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
	 * Fuzzy matching for worker name against one or multiple filters
	 * @param string            $workerName
	 * @param null|string|array $filters worker(s) filter
	 * @return bool
	 */
	private function filterWorkers($workerName, $filters) {
		if (!$filters) {
			return true;
		}
		if (!is_array($filters)) {
			$filters = array($filters);
		}
		foreach ($filters as $filter) {
			if (String::contains($workerName, $filter, false)) {
				return true;
			}
		}
		return false;
	}

	private function waitForProcess($pid) {
		while($this->isProcessRunning($pid)) {
			usleep(200 * 1000); // 200 milliseconds
		}
	}

	/**
	 * Checks if pid is running
	 * @param $pid
	 * @return bool
	 */
	private function isProcessRunning($pid) {
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
