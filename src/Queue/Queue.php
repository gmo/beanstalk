<?php
namespace GMO\Beanstalk\Queue;

use GMO\Common\String;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Exception\SocketException;
use Pheanstalk\Pheanstalk;
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
 */
class Queue implements QueueInterface {

	/**
	 * Use this method when running from command-line.
	 * Calls a method based on args, or displays usage.
	 * @param array $args command-line arguments
	 */
	public function runCommand($args) {
		$filename = basename($args[0]);

		function help($filename) {
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

		function getMethod($args) {
			$args = array_merge($args, array( "", "", "" ));

			$msg = "";
			$method = "help";
			$tube = "";

			switch ($args[1]) {
				case "delete":
					$tube = $args[3];
					switch ($args[2]) {
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
					switch ($args[2]) {
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

			return array( $method, $tube, $msg );
		}

		list ($method, $tube, $msg) = getMethod($args);

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

		switch ($method) {
			case "help":
				help($filename);
				break;
			case "getStats":
			case "getAllStats":
			case "getReadyJobsIn":
			case "getAllReadyJobs":
				$tubes = $tube ? $this->$method($tube) : $this->$method();
				$this->log->info(print_r($tubes, true));
				break;
			case "listTubes":
				$this->log->info(print_r($this->listTubes(), true));
				break;
			default:
				if ($tube) {
					$this->$method($tube);
					break;
				}
				foreach ($this->listTubes() as $tube) {
					$this->$method($tube);
				}
		}

	}

	/** @inheritdoc */
	public function push($tube, $data, $jsonEncode = true) {
		$data = $jsonEncode ? json_encode($data) : $data;
		$this->pheanstalk->useTube($tube)->put($data);
	}

	/** @inheritdoc */
	public function kickJobs($tube) {
		$stats = $this->stats($tube);
		$this->pheanstalk->useTube($tube)->kick($stats["current-jobs-buried"]);
		$this->pheanstalk->useTube($tube)->kick($stats["current-jobs-delayed"]);
	}

	/** @inheritdoc */
	public function deleteReadyJobs($tube) {
		$this->deleteJobs("peekReady", $tube);
	}

	/** @inheritdoc */
	public function deleteBuriedJobs($tube) {
		$this->deleteJobs("peekBuried", $tube);
	}

	/** @inheritdoc */
	public function deleteDelayedJobs($tube) {
		$this->deleteJobs("peekDelayed", $tube);
	}

	/** @inheritdoc */
	public function listTubes() {
		return $this->pheanstalk->listTubes();
	}

	/** @inheritdoc */
	public function stats($tube = null) {
		$tubes = array();
		foreach (array( $tube ) ?: $this->listTubes() as $tube) {
			$tubes[$tube] = $this->statsFromTube($tube);
		}
		return $tubes;
	}

	public function jobStats($job) {
		return $this->pheanstalk->statsJob($job);
	}

	/**
	 * Gets the number of ready jobs in a given tube
	 * @param string $tube
	 * @return int
	 */
	public function getNumberOfJobsReady($tube) {
		try {
			$stats = $this->pheanstalk->statsTube($tube);
			if (isset($stats["current-jobs-ready"])) {
				return $stats["current-jobs-ready"];
			}
		} catch (ServerException $e) {
		}
		return 0;
	}

	/**
	 * Gets all ready jobs and their data for every tube
	 * @return array
	 */
	public function getAllReadyJobs() {
		$tubes = array();
		foreach ($this->listTubes() as $tube) {
			$tubes[$tube] = $this->readyJobsFromTube($tube);
		}
		return $tubes;
	}

	/**
	 * Gets all ready jobs and their data for the given tube
	 * @param string $tube
	 * @return array
	 */
	public function readyJobsFromTube($tube) {
		$queuedJobs = array();

		try {
			while ($job = $this->pheanstalk->reserveFromTube($tube, 0)) {
				$this->buryJob($job);
				$queuedJobs[] = $job->getData();
			}
		} catch (ServerException $e) {
		}
		$this->kickJobs($tube);

		return $queuedJobs;
	}

	/** @inheritdoc */
	public function getJob($tube, $timeout = null) {
		try {
			return $this->pheanstalk->reserveFromTube($tube, $timeout);
		} catch (SocketException $e) {
			return false;
		}
	}

	/** @inheritdoc */
	public function buryJob($job) {
		$this->pheanstalk->bury($job);
	}

	/** @inheritdoc */
	public function deleteJob($job) {
		try {
			$this->pheanstalk->delete($job);
		} catch (ServerException $e) {
			$this->log->warning("Error deleting job", array( "exception" => $e ));
		}
	}

	/** @inheritdoc */
	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
	}

	/**
	 * Returns a list of tubes that match the tube given
	 * @param $tubeName
	 * @return array matched tubes
	 */
	protected function findTubeName($tubeName) {
		return array_filter($this->listTubes(), function ($tube) use ($tubeName) {
			return String::containsInsensitive($tube, $tubeName);
		});
	}

	protected function statsFromTube($tube) {
		/** @var \Pheanstalk\Response\ArrayResponse $response */
		$response = $this->pheanstalk->statsTube($tube);
		// Normalize ArrayObject to Array
		$stats = $response->getArrayCopy();
		return $stats;
	}

	/**
	 * Deletes all jobs of a given state for the given tube
	 * @param string $state
	 * @param string $tube
	 */
	private function deleteJobs($state, $tube) {
		try {
			while ($job = $this->pheanstalk->$state($tube)) {
				try {
					$this->pheanstalk->delete($job);
				} catch (ServerException $e) {
					$this->log->warning("Error deleting job", array( "exception" => $e ));
				}
			}
		} catch (ServerException $e) {
		}
	}

	/**
	 * Sets up a new Queue
	 *
	 * @param string          $host
	 * @param int             $port
	 * @param LoggerInterface $logger [Optional] Default: NullLogger
	 */
	public function __construct($host = 'localhost', $port = 11300, LoggerInterface $logger = null) {
		$this->pheanstalk = new Pheanstalk($host, $port);
		$this->setLogger($logger ?: new NullLogger());
	}

	/** @var Pheanstalk */
	protected $pheanstalk;
	/** @var LoggerInterface */
	protected $log;
}
