<?php
namespace GMO\Beanstalk;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Queue manages jobs in tubes and provides stats about jobs
 *
 * @example Push a job to a queue
 *          $queue = Queue::getInstance( $logger, $host, $port );
 *          $queue->push( "TubeA", array("message" => "Hello World") );
 *
 * @example queue.php file for command line usage
 *          $queue = Queue::getInstance( $logger, $host, $port );
 *          $queue->runCommand($argv);
 *
 * @package GMO\Beanstalk
 *
 * @since 1.0.0
 */
class Queue implements LoggerAwareInterface {

	/**
	 * Use this method when running from command-line.
	 * Calls a method based on args, or displays usage.
	 * @param array $args command-line arguments
	 */
	public function runCommand( $args ) {
		$filename = basename( $args[0] );

		function help( $filename ) {
			echo "php $filename [stats|view|delete|kick]\n";
			echo "              delete [ready|buried|delayed] (Default: ready)\n";
			echo "              kick   [buried|delayed]       (Default: buried)\n";
			exit(1);
		}

		function getMethod( $args, LoggerInterface $log ) {
			if ( !isset($args[1]) ) {
				return "help";
			}
			switch ( $args[1] ) {
				case "delete":
					if ( !isset($args[2]) ) {
						$log->info( "Deleting ready jobs" );
						return "deleteReadyJobs";
					}
					switch ( $args[2] ) {
						case "buried":
							$log->info( "Deleting buried jobs" );
							return "deleteBuriedJobs";
						case "delayed":
							$log->info( "Deleting delayed jobs" );
							return "deleteDelayedJobs";
						case "ready":
							$log->info( "Deleting ready jobs" );
							return "deleteReadyJobs";
					}
					return "help";
				case "stats":
					$log->info( "View queue stats" );
					return "getAllStats";
				case "view":
					$log->info( "View ready jobs" );
					return "getAllReadyJobs";
				case "kick":
					if ( !isset($args[2]) ) {
						$log->info( "Kicking buried jobs" );
						return "kickBuriedJobs";
					}
					switch ( $args[2] ) {
						case "delayed":
							$log->info( "Kicking delayed jobs" );
							return "kickDelayedJobs";
						case "ready":
							$log->info( "Cannot kick ready jobs" );
							return "help";
						case "buried":
							$log->info( "Kicking buried jobs" );
							return "kickBuriedJobs";
					}
					return "help";
			}
			return "help";
		}

		$method = getMethod( $args, $this->log );

		switch ( $method ) {
			case "help":
				help( $filename );
				break;
			case "getAllStats":
			case "getAllReadyJobs":
				$tubes = $this->$method();
				$this->log->info( print_r( $tubes, true ) );
				break;
			default:
				foreach ( $this->listTubes() as $tube ) {
					$this->$method( $tube );
				}
		}

	}

	/**
	 * Push a job to a queue
	 * @param string $tube tube name
	 * @param array  $data job data
	 * @param bool   $jsonEncode Optional. Default true.
	 */
	public function push( $tube, $data, $jsonEncode = true ) {
		$data = $jsonEncode ? json_encode( $data ) : $data;
		$this->pheanstalk->useTube( $tube )->put( $data );
	}

	// Public functions that change the state of jobs
	/**
	 * Kicks all buried jobs in a given tube
	 * @param string $tube
	 */
	public function kickBuriedJobs( $tube ) {
		$stats = $this->pheanstalk->statsTube( $tube );
		$this->pheanstalk->useTube( $tube )->kick( $stats["current-jobs-buried"] );
	}

	/**
	 * Kicks all delayed jobs in a given tube
	 * @param string $tube
	 */
	public function kickDelayedJobs( $tube ) {
		$stats = $this->pheanstalk->statsTube( $tube );
		$this->pheanstalk->useTube( $tube )->kick( $stats["current-jobs-delayed"] );
	}

	/**
	 * Deletes all ready jobs in a given tube
	 * @param string $tube
	 */
	public function deleteReadyJobs( $tube ) {
		$this->deleteJobs( "peekReady", $tube );
	}

	/**
	 * Deletes all buried jobs in a given tube
	 * @param string $tube
	 */
	public function deleteBuriedJobs( $tube ) {
		$this->deleteJobs( "peekBuried", $tube );
	}

	/**
	 * Deletes all delayed jobs in a given tube
	 * @param string $tube
	 */
	public function deleteDelayedJobs( $tube ) {
		$this->deleteJobs( "peekDelayed", $tube );
	}

	/**
	 * Deletes all jobs of a given state for the given tube
	 * @param string $state
	 * @param string $tube
	 */
	private function deleteJobs( $state, $tube ) {
		try {
			while ( $job = $this->pheanstalk->$state( $tube ) ) {
				try {
					$this->pheanstalk->delete( $job );
				} catch ( \Pheanstalk_Exception_ServerException $e ) {
					$this->log->warning( "Error deleting job", array("exception" => $e) );
				}
			}
		} catch ( \Pheanstalk_Exception_ServerException $e ) { }
	}

	// Public functions that returns information about the queues
	/**
	 * Returns the names of all the tubes
	 * @return array
	 */
	public function listTubes() {
		return $this->pheanstalk->listTubes();
	}

	/**
	 * Gets the number of ready jobs in a given tube
	 * @param string $tube
	 * @return int
	 */
	public function getNumberOfJobsReady( $tube ) {
		try {
			$stats = $this->pheanstalk->statsTube( $tube );
			if ( isset($stats["current-jobs-ready"]) ) {
				return $stats["current-jobs-ready"];
			}
		} catch ( \Pheanstalk_Exception_ServerException $e ) { }
		return 0;
	}

	/**
	 * Gets the stats for every tube
	 * @return array
	 */
	public function getAllStats() {
		$tubes = array();
		foreach ( $this->listTubes() as $tube ) {
			$tubes[$tube] = $this->getStats( $tube );
		}
		return $tubes;
	}

	/**
	 * Gets the stats for the given tube
	 * @param string $tube
	 * @return array
	 */
	public function getStats( $tube ) {
		$response = $this->pheanstalk->statsTube( $tube );
		// Normalize ArrayObject to Array
		$stats = $response->getArrayCopy();
		return $stats;
	}

	/**
	 * Gets all ready jobs and their data for every tube
	 * @return array
	 */
	public function getAllReadyJobs() {
		$tubes = array();
		foreach ( $this->listTubes() as $tube ) {
			$tubes[$tube] = $this->getReadyJobsIn( $tube );
		}
		return $tubes;
	}

	/**
	 * Gets all ready jobs and their data for the given tube
	 * @param string $tube
	 * @return array
	 */
	public function getReadyJobsIn( $tube ) {
		$queuedJobs = array();

		try {
			$num = $this->getNumberOfJobsReady( $tube );

			for ( $i = 0; $i < $num; $i++ ) {
				$job = $this->pheanstalk->watchOnly( $tube )->reserve();
				$this->pheanstalk->bury( $job );

				$queuedJobs[] = $job->getData();
			}

			$this->kickBuriedJobs( $tube );

		} catch ( \Pheanstalk_Exception_ServerException $e ) {
		}

		return $queuedJobs;
	}

	// Functions to setup object
	/**
	 * Sets a logger instance on the object
	 * @param LoggerInterface $logger
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->log = $logger;
	}

	/**
	 * @deprecated Use constructor instead
	 * @param string $host
	 * @param int    $port
	 * @param string $logger [Optional] Default: NullLogger
	 * @return Queue
	 */
	public static function getInstance( $host, $port, $logger = null ) {
		$queueClass = get_called_class();
		return new $queueClass($host, $port, $logger);
	}

	/**
	 * Sets up a new Queue
	 *
	 * @example new Queue($host, $port, LoggerInterface $logger = null)
	 * @example BC: new Queue($logger, $host, $port)
	 *
	 * @param string $host
	 * @param int    $port
	 * @param string $logger [Optional] Default: NullLogger
	 */
	public function __construct( $host, $port, $logger = null ) {
		$args = func_get_args();

		if ($args[0] instanceof LoggerInterface) {
			$logger = array_shift($args);
			$this->log = $logger;
		}

		if ($args[0] === null) {
			array_shift($args);
		}

		$this->pheanstalk = new \Pheanstalk_Pheanstalk($args[0], $args[1]);

		if (isset($args[2]) && $args[2] instanceof LoggerInterface) {
			$this->log = $args[2];
		}

		if (!$this->log) {
			$this->log = new NullLogger();
		}
	}

	/** @var \Pheanstalk_Pheanstalk */
	protected $pheanstalk;

	/** @var LoggerInterface */
	private $log;

}
