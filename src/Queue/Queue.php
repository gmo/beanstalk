<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job;
use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\ISerializable;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Exception\SocketException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Response\ArrayResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Queue manages jobs in tubes and provides stats about jobs
 *
 * @package GMO\Beanstalk
 */
class Queue implements QueueInterface {

	//region Tube Control

	public function push($tube, $data, $priority = null, $delay = null, $ttr = null) {
		if ($data instanceof ISerializable) {
			$data = $data->toJson();
		} elseif ($data instanceof \Traversable) {
			$data = iterator_to_array($data, true);
		}
		if (is_array($data)) {
			$data = json_encode($data);
		}

		return $this->pheanstalk->putInTube(
			$tube,
			$data,
			$priority ?: Pheanstalk::DEFAULT_PRIORITY,
			$delay ?: Pheanstalk::DEFAULT_DELAY,
			$ttr ?: Pheanstalk::DEFAULT_TTR
		);
	}

	public function reserve($tube, $timeout = null) {
		try {
			$job = $this->pheanstalk->reserveFromTube($tube, $timeout);
			return new Job($job->getId(), $job->getData());
		} catch (SocketException $e) {
			return false;
		}
	}

	public function kickTube($tube) {
		$stats = $this->statsTube($tube);
		if ($stats->buriedJobs() > 0) {
			$this->pheanstalk->kick($stats->buriedJobs());
		}
		$this->pheanstalk->kick($stats->delayedJobs());
	}

	public function deleteReadyJobs($tube) {
		$this->deleteJobs("peekReady", $tube);
	}

	public function deleteBuriedJobs($tube) {
		$this->deleteJobs("peekBuried", $tube);
	}

	public function deleteDelayedJobs($tube) {
		$this->deleteJobs("peekDelayed", $tube);
	}

	private function deleteJobs($state, $tube) {
		try {
			while ($job = $this->pheanstalk->$state($tube)) {
				$this->delete($job);
			}
		} catch (ServerException $e) {
		}
	}

	public function pause($tube, $delay) {
		$this->pheanstalk->pauseTube($tube, $delay);
	}

	/** @inheritdoc */
	public function statsTube($tube) {
		/** @var ArrayResponse $response */
		$response = $this->pheanstalk->statsTube($tube);
		$stats = TubeStats::create($response);
		return $stats;
	}

	//endregion

	//region Job Control

	public function release(Job $job, $priority = null, $delay = null) {
		$priority = $priority ?: Pheanstalk::DEFAULT_PRIORITY;
		$delay = $delay ?: Pheanstalk::DEFAULT_DELAY;
		$this->pheanstalk->release($job, $priority, $delay);
	}

	public function bury(Job $job, $priority = null) {
		$priority = $priority ?: Pheanstalk::DEFAULT_PRIORITY;
		$this->pheanstalk->bury($job, $priority);
	}

	public function delete(Job $job) {
		try {
			$this->pheanstalk->delete($job);
		} catch (ServerException $e) {
			$this->log->notice("Error deleting job", array( "exception" => $e ));
		}
	}

	public function kickJob($job) {
		$this->pheanstalk->kickJob($job);
	}

	public function statsJob($job) {
		/** @var ArrayResponse $stats */
		$stats = $this->pheanstalk->statsJob($job);
		return JobStats::create($stats);
	}

	public function touch(Job $job) {
		$this->pheanstalk->touch($job);
	}

	//endregion

	public function listTubes() {
		return ArrayCollection::create($this->pheanstalk->listTubes());
	}

	/** @inheritdoc */
	public function statsAllTubes() {
		$tubes = new ArrayCollection();
		foreach ($this->listTubes() as $tube) {
			$tubes->set($tube, $this->statsTube($tube));
		}
		return $tubes;
	}

	/** @inheritdoc */
	public function statsServer() {
		/** @var ArrayResponse $stats */
		$stats = $this->pheanstalk->stats();
		return ServerStats::create($stats);
	}

	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
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
