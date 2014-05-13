<?php
namespace GMO\Beanstalk;

use GMO\Common\String;
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
			echo "Usage:\n";
			echo "  php $filename stats|view|list|delete|kick [tube]\n";
			echo "                kick   [buried|delayed]       (Default: buried)\n";
			echo "                delete [ready|buried|delayed] (Default: ready)\n";
			echo "\n";
			echo "Examples:\n";
			echo "  php $filename stats\n";
			echo "  php $filename kick ExampleTube\n";
			echo "  php $filename delete buried ExampleTube\n";
			echo "\n";
			echo "Args in [] are optional.\n";
			echo "If no tube is specified command is applied to all tubes.\n";
			echo "Tube name can be approximate.\n";
			echo "\n\n";
			exit(1);
		}

		function getMethod( $args ) {
			$args = array_merge($args, array("", "", ""));

			$msg = "";
			$method = "help";
			$tube = "";

			switch ( $args[1] ) {
				case "delete":
					$tube = $args[3];
					switch ( $args[2] ) {
						case "buried":
							$msg = "Deleting buried jobs";
							$method = "deleteBuriedJobs";
							break;
						case "delayed":
							$msg = "Deleting delayed jobs";
							$method = "deleteDelayedJobs";
							break;
						case "ready":
							$msg = "Deleting ready jobs";
							$method = "deleteReadyJobs";
							break;
						default:
							if (!$tube) {
								$tube = $args[2];
							}
							$msg = "Deleting ready jobs";
							$method = "deleteReadyJobs";
							break;
					}
					break;
				case "stats":
					$msg = "View queue stats";
					$tube = $args[2];
					$method = $tube ? "getStats" : "getAllStats";
					break;
				case "view":
					$msg = "View ready jobs";
					$tube = $args[2];
					$method = $tube ? "getReadyJobsIn" : "getAllReadyJobs";
					break;
				case "kick":
					$tube = $args[3];
					switch ( $args[2] ) {
						case "delayed":
							$msg = "Kicking delayed jobs";
							$method = "kickDelayedJobs";
							break;
						case "ready":
							$msg = "Cannot kick ready jobs";
							$method = "help";
							break;
						case "buried":
							$msg = "Kicking buried jobs";
							$method = "kickBuriedJobs";
							break;
						default:
							if (!$tube) {
								$tube = $args[2];
							}
							$msg = "Kicking buried jobs";
							$method = "kickBuriedJobs";
							break;
					}
					break;
				case "list":
					$msg = "List tubes";
					$method = "listTubes";
					break;
			}

			return array($method, $tube, $msg);
		}
		list ($method, $tube, $msg) = getMethod( $args );

		if ($tube) {
			$tubesMatched = $this->findTubeName($tube);
			if (empty($tubesMatched)) {
				$this->log->info("Tube doesn't exist");
				$method = "listTubes";
			} elseif (count($tubesMatched) > 1) {
				$this->log->info("Multiple tubes found, not sure which one to pick");
				$method = "listTubes";
			} else {
				$tube = reset($tubesMatched);
				$this->log->info("$msg in $tube");
			}
		} elseif (!empty($msg)) {
			$this->log->info($msg);
		}

		switch ( $method ) {
			case "help":
				help( $filename );
				break;
			case "getStats":
			case "getAllStats":
			case "getReadyJobsIn":
			case "getAllReadyJobs":
				$tubes = $tube ? $this->$method($tube) : $this->$method();
				$this->log->info( print_r( $tubes, true ) );
				break;
			case "listTubes":
				$this->log->info( print_r( $this->listTubes(), true ) );
				break;
			default:
				if ($tube) {
					$this->$method($tube);
					break;
				}
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

	/**
	 * Returns a list of tubes that match the tube given
	 * @param $tubeName
	 * @return array matched tubes
	 */
	protected function findTubeName($tubeName) {
		return array_filter($this->listTubes(), function($tube) use ($tubeName) {
				return String::containsInsensitive($tube, $tubeName);
			});
	}

	/** @var \Pheanstalk_Pheanstalk */
	protected $pheanstalk;

	/** @var LoggerInterface */
	private $log;

}
