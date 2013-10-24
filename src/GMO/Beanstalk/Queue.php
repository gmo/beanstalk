<?php
namespace GMO\Beanstalk;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

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
			switch ( $args[1] ) {
				case "delete":
					if ( !$args[2] ) {
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
					return "getStats";
				case "view":
					$log->info( "View ready jobs" );
					return "getReadyJobsIn";
				case "kick":
					if ( !$args[2] ) {
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

		if ( count( $args ) == 1 ) {
			help( $filename );
		}

		$method = getMethod( $args, $this->log );

		switch ( $method ) {
			case "help":
				help( $filename );
				break;
			case "getStats":
			case "getReadyJobsIn":
				$tubes = array();
				foreach ( $this->listTubes() as $tube ) {
					$tubes[$tube] = $this->$method( $tube );
				}
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
	 */
	public function push( $tube, $data ) {
		$data = json_encode( $data );
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
		$stats = $this->pheanstalk->statsTube( $tube );
		if ( isset($stats["current-jobs-ready"]) ) {
			return $stats["current-jobs-ready"];
		}

		return 0;
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
	 * Gets all ready jobs and their data for the given tube
	 * @param string $tube
	 * @return array
	 */
	public function getReadyJobsIn( $tube ) {
		$queuedJobs = array();

		try {
			$stats = $this->pheanstalk->statsTube( $tube );

			for ( $i = 0; $i < $stats["current-jobs-ready"]; $i++ ) {
				$job = $this->pheanstalk->watch( $tube )->ignore( 'default' )->reserve();
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
	 * Gets a singleton instance of Queue
	 * @param LoggerInterface $logger
	 * @param string          $host
	 * @param int             $port
	 * @return Queue
	 */
	public static function getInstance( LoggerInterface $logger, $host, $port ) {
		if ( self::$instance == null ) {
			self::$instance = new Queue($logger, $host, $port);
		}

		return self::$instance;
	}

	/**
	 * Sets up a new Queue
	 * @param LoggerInterface $logger
	 * @param string          $host
	 * @param int             $port
	 */
	private function __construct( LoggerInterface $logger, $host, $port ) {
		$this->pheanstalk = new \Pheanstalk_Pheanstalk($host, $port);
		$this->log = $logger;
	}

	/** @var Queue */
	private static $instance;

	/** @var \Pheanstalk_Pheanstalk */
	private $pheanstalk;

	/** @var LoggerInterface */
	private $log;

}