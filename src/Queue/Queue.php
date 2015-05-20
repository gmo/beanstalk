<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Exception\JobPushException;
use GMO\Beanstalk\Exception\JobTooBigException;
use GMO\Beanstalk\Exception\RangeException;
use GMO\Beanstalk\Helper\JobDataSerializer;
use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Job\UnserializableJob;
use GMO\Beanstalk\Log\JobProcessor;
use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Exception\NotSerializableException;
use Pheanstalk;
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
		$priority = $priority ?: static::DEFAULT_PRIORITY;
		if ($priority < 0 || $priority > 4294967295) {
			throw new RangeException("Priority must be between 0 and 4294967295. Given: $priority");
		}
		try {
			$data = $this->serializer->serialize($data);
			return $this->pheanstalk->putInTube(
				$tube,
				$data,
				$priority,
				$delay ?: static::DEFAULT_DELAY,
				$ttr ?: static::DEFAULT_TTR
			);
		} catch (Pheanstalk\Exception $e) {
			if ($e->getMessage() === Pheanstalk\Response::RESPONSE_JOB_TOO_BIG) {
				throw new JobTooBigException($tube, $data, $e);
			}
			throw new JobPushException($tube, $data, $e);
		}
	}

	public function reserve($tube, $timeout = null, $stopWatching = false) {
		try {
			$job = $this->pheanstalk->reserveFromTube($tube, $timeout);
			if ($stopWatching) {
				$this->pheanstalk->watchOnly(Pheanstalk\PheanstalkInterface::DEFAULT_TUBE);
			}
			if (!$job) {
				$this->logProcessor->setCurrentJob(null);
				return new NullJob();
			}

			return $this->createJob($job);
		} catch (Pheanstalk\Exception\ClientException $e) {
			$this->logProcessor->setCurrentJob(null);
			return new NullJob();
		}
	}

	public function kickTube($tube, $num = -1) {
		$this->pheanstalk->useTube($tube);
		$kicked = 0;

		$stats = $this->statsTube($tube);
		if ($stats->buriedJobs() > 0) {
			$numToKick = $num > 0 ? min($num, $stats->buriedJobs()) : $stats->buriedJobs();
			$kicked += $this->pheanstalk->kick($numToKick);
			$num -= $numToKick;
			if ($num === 0) {
				return $kicked;
			}
		}
		$numToKick = $num > 0 ? $num : $stats->delayedJobs();
		$kicked += $this->pheanstalk->kick($numToKick);

		return $kicked;
	}

	public function peekJob($jobId) {
		try {
			$job = $this->pheanstalk->peek($jobId);
		} catch (Pheanstalk\Exception\ServerException $e) {
			return new NullJob();
		}
		return $this->createJob($job);
	}

	public function peekReady($tube) {
		return $this->peek($tube, 'Ready');
	}

	public function peekBuried($tube) {
		return $this->peek($tube, 'Buried');
	}

	public function peekDelayed($tube) {
		return $this->peek($tube, 'Delayed');
	}

	private function peek($tube, $state) {
		try {
			$job = $this->pheanstalk->{"peek$state"}($tube);
		} catch (Pheanstalk\Exception\ServerException $e) {
			return new NullJob();
		}
		return $this->createJob($job);
	}

	public function deleteReadyJobs($tube, $num = -1) {
		return $this->deleteJobs("Ready", $tube, $num);
	}

	public function deleteBuriedJobs($tube, $num = -1) {
		return $this->deleteJobs("Buried", $tube, $num);
	}

	public function deleteDelayedJobs($tube, $num = -1) {
		return $this->deleteJobs("Delayed", $tube, $num);
	}

	private function deleteJobs($state, $tube, $numberToDelete) {
		$numberDeleted = 0;
		while ($numberToDelete !== 0) {
			$job = $this->{"peek$state"}($tube);
			if ($this->isNullJob($job)) {
				break;
			}
			$this->delete($job);
			$numberDeleted++;
			$numberToDelete--;
		}
		return $numberDeleted;
	}

	public function pause($tube, $delay) {
		$this->pheanstalk->pauseTube($tube, $delay);
	}

	/** @inheritdoc */
	public function statsTube($tube) {
		/** @var Pheanstalk\Response\ArrayResponse $response */
		$response = $this->pheanstalk->statsTube($tube);
		$stats = TubeStats::create($response);
		return $stats;
	}

	//endregion

	//region Job Control

	public function release(Job $job, $priority = null, $delay = null) {
		if ($this->isNullJob($job)) {
			return;
		}
		$priority = $priority ?: Pheanstalk\Pheanstalk::DEFAULT_PRIORITY;
		$delay = $delay ?: Pheanstalk\Pheanstalk::DEFAULT_DELAY;
		$this->pheanstalk->release($job, $priority, $delay);
	}

	public function bury(Job $job, $priority = null) {
		if ($this->isNullJob($job)) {
			return;
		}
		$priority = $priority ?: Pheanstalk\Pheanstalk::DEFAULT_PRIORITY;
		$this->pheanstalk->bury($job, $priority);
	}

	public function delete($job) {
		if ($this->isNullJob($job)) {
			return;
		}
		try {
			$this->pheanstalk->delete($job);
		} catch (Pheanstalk\Exception\ServerException $e) {
			$this->log->notice("Error deleting job", array( "exception" => $e ));
		}
	}

	public function kickJob($job) {
		if ($this->isNullJob($job)) {
			return;
		}
		$this->pheanstalk->kickJob($job);
	}

	/** @inheritdoc */
	public function statsJob($job) {
		try {
			/** @var Pheanstalk\Response\ArrayResponse $stats */
			$stats = $this->pheanstalk->statsJob($job);
		} catch (Pheanstalk\Exception\ServerException $e) {
			$stats = array('id' => -1);
		}
		return JobStats::create($stats);
	}

	public function touch(Job $job) {
		if ($this->isNullJob($job)) {
			return;
		}
		$this->pheanstalk->touch($job);
	}

	//endregion

	public function listTubes() {
		$tubes = ArrayCollection::create($this->pheanstalk->listTubes())
			->sortValues();
		$tubes->removeElement(Pheanstalk\PheanstalkInterface::DEFAULT_TUBE);
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
	public function statsServer() {
		/** @var Pheanstalk\Response\ArrayResponse $stats */
		$stats = $this->pheanstalk->stats();
		return ServerStats::create($stats);
	}

	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
	}

	/**
	 * Gets a monolog processor that will add current job info.
	 * Useful for workers.
	 *
	 * @return JobProcessor
	 */
	public function getJobProcessor() {
		return $this->logProcessor;
	}

	/**
	 * Sets up a new Queue
	 *
	 * @param string          $host
	 * @param int             $port
	 * @param LoggerInterface $logger [Optional] Default: NullLogger
	 */
	public function __construct($host = 'localhost', $port = 11300, LoggerInterface $logger = null) {
		$this->setLogger($logger ?: new NullLogger());
		$this->pheanstalk = new Pheanstalk\Pheanstalk($host, $port);
		$this->logProcessor = new JobProcessor($this);
		$this->serializer = new JobDataSerializer();
	}

	protected function createJob(Pheanstalk\Job $job) {
		try {
			$data = $this->serializer->unserialize($job->getData());
			$job = new Job($job->getId(), $data, $this);
		} catch (NotSerializableException $e) {
			$job = new UnserializableJob($job->getId(), $job->getData(), $this, $e);
		}
		$this->logProcessor->setCurrentJob($job);
		return $job;
	}

	protected function isNullJob(Job $job) {
		return $job instanceof NullJob || $job->getId() === -1;
	}

	/** @var Pheanstalk\Pheanstalk */
	protected $pheanstalk;
	/** @var LoggerInterface */
	protected $log;
	/** @var JobProcessor */
	protected $logProcessor;
	/** @var JobDataSerializer */
	protected $serializer;
}
