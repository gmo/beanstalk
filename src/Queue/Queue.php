<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Exception\NotSerializableException;
use GMO\Common\ISerializable;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Exception\SocketException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
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
		return $this->pheanstalk->putInTube(
			$tube,
			$this->serializeJobData($data),
			$priority ?: static::DEFAULT_PRIORITY,
			$delay ?: static::DEFAULT_DELAY,
			$ttr ?: static::DEFAULT_TTR
		);
	}

	public function reserve($tube, $timeout = null, $stopWatching = false) {
		try {
			$job = $this->pheanstalk->reserveFromTube($tube, $timeout);
			if ($stopWatching) {
				$this->pheanstalk->watchOnly(PheanstalkInterface::DEFAULT_TUBE);
			}
			if (!$job) {
				return new NullJob();
			}

			return new Job($job->getId(), $this->unserializeJobData($job->getData()), $this);
		} catch (SocketException $e) {
			return new NullJob();
		}
	}

	public function kickTube($tube) {
		$this->pheanstalk->useTube($tube);
		$kicked = 0;
		$stats = $this->statsTube($tube);
		if ($stats->buriedJobs() > 0) {
			$kicked += $this->pheanstalk->kick($stats->buriedJobs());
		}
		$kicked += $this->pheanstalk->kick($stats->delayedJobs());
		$this->pheanstalk->ignore($tube);
		return $kicked;
	}

	}

	}

	}


	public function deleteReadyJobs($tube, $num = -1) {
		return $this->deleteJobs("peekReady", $tube, $num);
	}

	public function deleteBuriedJobs($tube, $num = -1) {
		return $this->deleteJobs("peekBuried", $tube, $num);
	}

	public function deleteDelayedJobs($tube, $num = -1) {
		return $this->deleteJobs("peekDelayed", $tube, $num);
	}

	private function deleteJobs($state, $tube, $numberToDelete) {
		$numberDeleted = 0;
		try {
			while ($numberToDelete !== 0 && $job = $this->pheanstalk->$state($tube)) {
				$this->delete($job);
				$numberDeleted++;
				$numberToDelete--;
			}
		} catch (ServerException $e) {
		}
		return $numberDeleted;
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

	public function delete($job) {
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
		$tubes = ArrayCollection::create($this->pheanstalk->listTubes());
		$tubes->removeElement(PheanstalkInterface::DEFAULT_TUBE);
		return $tubes;
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
	public function serverStats() {
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

	protected function serializeJobData($data) {
		if ($data instanceof ISerializable) {
			return $data->toJson();
		}
		if ($data instanceof \Traversable) {
			$data = iterator_to_array($data, true);
		}
		if (is_scalar($data)) {
			$data = array( 'data' => $data );
		}
		if (is_array($data)) {
			foreach ($data as $key => &$value) {
				if ($value instanceof ISerializable) {
					$value = $value->toArray();
				}
			}
			$data = json_encode($data);
		}
		return $data;
	}

	protected function unserializeJobData($data) {
		$params = new ArrayCollection(json_decode($data, true));
		if ($params->count() === 1 && $params->containsKey('data')) {
			return $params['data'];
		}

		if ($params->containsKey('class')) {
			try {
				/** @var ISerializable $cls */
				$cls = $params['class'];
				return $cls::fromArray($params->toArray());
			} catch (NotSerializableException $e) {
				return $params;
			}
		}

		foreach ($params as $key => $value) {
			if (is_string($value)) {
				$params[$key] = trim($value);
			} elseif (is_array($value) && array_key_exists('class', $value)) {
				try {
					/** @var ISerializable $cls */
					$cls = $value['class'];
					$params[$key] = $cls::fromArray($value);
				} catch (NotSerializableException $e) { }
			}
		}
		return $params;
	}

	/** @var Pheanstalk */
	protected $pheanstalk;
	/** @var LoggerInterface */
	protected $log;
}
