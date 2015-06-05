<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Exception\RangeException;
use GMO\Beanstalk\Helper\JobDataSerializer;
use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Log\JobProcessor;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Beanstalk\Tube\TubeCollection;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\DateTime;
use Psr\Log\LoggerInterface;

/**
 * ArrayQueue is an in-memory implementation of the QueueInterface.
 *
 * Most useful for testing.
 *
 * Note: Somethings are not implemented; notably job TTR and server stats.
 */
class ArrayQueue implements QueueInterface {

	public function release(Job $job, $priority = null, $delay = null) {
		if ($this->isNullJob($job)) {
			return;
		}
		/** @var ArrayJob $job */
		$stats = $this->jobStats[$job->getId()];
		$stats->set('releases', $stats->releases() + 1);

		$tube = $this->tube($stats->tube());
		$tube->reserved()->removeElement($job);

		$job->setDelay($delay);
		$job->setPriority($priority);
		if ($delay > 0) {
			$stats->set('state', 'delayed');
			$tube->delayed()->add($job);
		} else {
			$stats->set('state', 'ready');
			$tube->ready()->add($job);
		}
	}

	public function bury(Job $job, $priority = null) {
		if ($this->isNullJob($job)) {
			return;
		}
		/** @var ArrayJob $job */
		$job->setPriority($priority);

		$stats = $this->jobStats[$job->getId()];

		$tube = $this->tube($stats->tube());
		$tube->reserved()->removeElement($job);

		$stats->set('state', 'buried');
		$stats->set('buries', $stats->buries() + 1);
		$tube->buried()->add($job);
	}

	public function delete($job) {
		if ($this->isNullJob($job)) {
			return;
		}
		$stats = $this->jobStats[$job->getId()];

		$tube = $this->tube($stats->tube());
		$tube->{$stats->state()}()->removeElement($job);

		$this->jobStats->remove($job->getId());
		$tube->incrementDeleteCount();
		$this->removeEmptyTube($tube);
	}

	public function kickJob($job) {
		if ($this->isNullJob($job)) {
			return;
		}
		$stats = $this->jobStats[$job->getId()];

		$tube = $this->tube($stats->tube());
		if ($job instanceof ArrayJob && $job->isDelayed()) {
			$tube->delayed()->removeElement($job);
		} else {
			$tube->buried()->removeElement($job);
		}

		$stats->set('state', 'ready');
		$stats->set('kicks', $stats->kicks() + 1);
		$tube->ready()->add($job);
		if ($job instanceof ArrayJob) {
			$job->setDelay(0);
		}
	}

	/** @inheritdoc */
	public function statsJob($job) {
		$id = $job instanceof Job ? $job->getId() : $job;
		$stats = $this->jobStats[$id];
		/** @var DateTime $created */
		$created = $stats->get('created');
		$stats->set('age', $created->diff(new DateTime())->s);

		return $stats;
	}

	public function touch(Job $job) { }

	public function setLogger(LoggerInterface $logger) { }

	public function listTubes() {
		return $this->tubes->getKeys();
	}

	public function statsAllTubes() {
		$stats = new ArrayCollection();
		foreach ($this->tubes as $tube) {
			$stats->set($tube, $this->statsTube($tube));
		}
		return $stats;
	}

	public function statsServer() {
		return new ServerStats();
	}

	public function push($tube, $data, $priority = null, $delay = null, $ttr = null) {
		$priority = $priority ?: static::DEFAULT_PRIORITY;
		if ($priority < 0 || $priority > 4294967295) {
			throw new RangeException("Priority must be between 0 and 4294967295. Given: $priority");
		}
		$delay = $delay ?: static::DEFAULT_DELAY;

		$data = $this->serializer->serialize($data);
		$job = new ArrayJob($this->jobCounter++, $data, $this);
		$job->setDelay($delay);
		$job->setPriority($priority);
		$this->jobStats->set($job->getId(), new JobStats(array(
			'id' => $job->getId(),
			'tube' => $tube,
			'state' => $delay > 0 ? 'delayed' : 'ready',
			'pri' => $priority,
			'created' => new DateTime(),
		)));
		$tube = $this->tube($tube);
		if ($delay > 0) {
			$tube->delayed()->add($job);
		} else {
			$tube->ready()->add($job);
		}
		$tube->incrementJobCount();

		return $job->getId();
	}

	public function reserve($tube, $timeout = null, $stopWatching = false) {
		$tube = $this->tube($tube);

		if ($tube->isPaused()) {
			$this->logProcessor->setCurrentJob(null);
			return new NullJob();
		}

		/** @var ArrayJob|null $job */
		$job = $tube->ready()->removeFirst();
		$this->logProcessor->setCurrentJob($job);
		if (!$job) {
			return new NullJob();
		}
		$stats = $this->jobStats[$job->getId()];

		$stats->set('state', 'reserved');
		$stats->set('reserves', $stats->reserves() + 1);
		$tube->reserved()->add($job);

		$job->setData($this->serializer->unserialize($job->getData()));
		$job->resetHandled();

		return $job;
	}

	public function kickTube($tube, $num = -1) {
		$tube = $this->tube($tube);

		$kicked = 0;
		if (($buriedCount = $tube->buried()->count()) > 0) {
			$numToKick = $num > 0 ? min($num, $buriedCount) : $buriedCount;
			$kicked += $numToKick;
			$num -= $numToKick;

			/** @var ArrayJob[] $jobsToKick */
			$jobsToKick = $tube->buried()->slice(0, $numToKick);
			foreach ($jobsToKick as $job) {
				$this->kickJob($job);
			}

			if ($num === 0) {
				return $kicked;
			}
		}
		$numToKick = $tube->delayed()->count();
		if ($num > 0) {
			$numToKick = min($numToKick, $num);
		}
		$kicked += $numToKick;

		/** @var ArrayJob[] $jobsToKick */
		$jobsToKick = $tube->delayed()->slice(0, $numToKick);
		foreach ($jobsToKick as $job) {
			$this->kickJob($job);
		}

		return $kicked;
	}

	public function peekJob($jobId) {
		$tubeName = $this->jobStats[$jobId]->tube();
		$tube = $this->tube($tubeName);
		return $this->getJobFromTubeWithId($tube, $jobId);
	}

	public function peekReady($tube) {
		return $this->tube($tube)->ready()->first() ?: new NullJob();
	}

	public function peekBuried($tube) {
		return $this->tube($tube)->buried()->first() ?: new NullJob();
	}

	public function peekDelayed($tube) {
		return $this->tube($tube)->delayed()->first() ?: new NullJob();
	}

	public function deleteReadyJobs($tube, $num = -1) {
		$tube = $this->tube($tube);

		$readyCount = $tube->ready()->count();
		$numToDelete = $num > 0 ? min($num, $readyCount) : $readyCount;
		/** @var ArrayJob[] $jobsToDelete */
		$jobsToDelete = $tube->ready()->slice(0, $numToDelete);
		foreach ($jobsToDelete as $job) {
			$this->delete($job);
		}

		$this->removeEmptyTube($tube);
	}

	public function deleteBuriedJobs($tube, $num = -1) {
		$tube = $this->tube($tube);

		$buriedCount = $tube->buried()->count();
		$numToDelete = $num > 0 ? min($num, $buriedCount) : $buriedCount;
		/** @var ArrayJob[] $jobsToDelete */
		$jobsToDelete = $tube->buried()->slice(0, $numToDelete);
		foreach ($jobsToDelete as $job) {
			$this->delete($job);
		}

		$this->removeEmptyTube($tube);
	}

	public function deleteDelayedJobs($tube, $num = -1) {
		$tube = $this->tube($tube);

		$delayedCount = $tube->delayed()->count();
		$numToDelete = $num > 0 ? min($num, $delayedCount) : $delayedCount;
		/** @var ArrayJob[] $jobsToDelete */
		$jobsToDelete = $tube->delayed()->slice(0, $numToDelete);
		foreach ($jobsToDelete as $job) {
			$this->delete($job);
		}

		$this->removeEmptyTube($tube);
	}

	public function pause($tube, $delay) {
		$tube = $this->tube($tube);
		$tube->pause($delay);
	}

	public function statsTube($tube) {
		if (!$this->tubes->containsKey($tube)) {
			return TubeStats::create()
				->set('name', $tube);
		}
		return $this->tubes[$tube]->getStats()
			->set('name', $tube);
	}

	/**
	 * @param string $tube
	 * @return ArrayTube
	 */
	public function tube($tube) {
		if (!$this->tubes->containsKey($tube)) {
			$this->tubes[$tube] = new ArrayTube($tube, $this);
		}
		return $this->tubes->get($tube);
	}

	public function tubes() {
		return $this->tubes;
	}

	public function getJobProcessor() {
		return $this->logProcessor;
	}

	public function __construct() {
		$this->tubes = new TubeCollection();
		$this->jobStats = new ArrayCollection();
		$this->logProcessor = new JobProcessor();
		$this->serializer = new JobDataSerializer();
	}

	protected function removeEmptyTube(ArrayTube $tube) {
		if (!$tube->isPaused() && $tube->isEmpty()) {
			$this->tubes->removeElement($tube);
		}
	}

	protected function getJobFromTubeWithId(ArrayTube $tube, $jobId) {
		$filter = function(ArrayJob $job) use ($jobId) {
			return $job->getId() === $jobId;
		};

		$job = $tube->ready()->filter($filter)->first();
		if ($job) {
			return $job;
		}

		$job = $tube->reserved()->filter($filter)->first();
		if ($job) {
			return $job;
		}

		$job = $tube->buried()->filter($filter)->first();
		if ($job) {
			return $job;
		}

		$job = $tube->delayed()->filter($filter)->first();
		if ($job) {
			return $job;
		}

		return new NullJob();
	}

	protected function isNullJob(Job $job) {
		return $job->getId() === -1;
	}

	/** @var TubeCollection|ArrayTube[] */
	protected $tubes;

	/** @var ArrayCollection|JobStats[] */
	protected $jobStats;

	protected $jobCounter = 0;

	/** @var JobProcessor */
	protected $logProcessor;

	/** @var JobDataSerializer */
	protected $serializer;
}
