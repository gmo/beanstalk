<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
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
		/** @var ArrayJob $job */
		$stats = $this->jobStats[$job->getId()];
		$stats->set('releases', $stats->releases() + 1);

		$tube = $this->getTube($stats->tube());
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
		/** @var ArrayJob $job */
		$job->setPriority($priority);

		$stats = $this->jobStats[$job->getId()];

		$tube = $this->getTube($stats->tube());
		$tube->reserved()->removeElement($job);

		$stats->set('state', 'buried');
		$stats->set('buries', $stats->buries() + 1);
		$tube->buried()->add($job);
	}

	public function delete($job) {
		$stats = $this->jobStats[$job->getId()];

		$tube = $this->getTube($stats->tube());
		$tube->reserved()->removeElement($job);

		$this->jobStats->remove($job->getId());
		$tube->incrementDeleteCount();
		$this->removeEmptyTube($tube);
	}

	public function kickJob($job) {
		$stats = $this->jobStats[$job->getId()];

		$tube = $this->getTube($stats->tube());
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

	public function serverStats() {
		return new ServerStats();
	}

	public function push($tube, $data, $priority = null, $delay = null, $ttr = null) {
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
		$tube = $this->getTube($tube);
		if ($delay > 0) {
			$tube->delayed()->add($job);
		} else {
			$tube->ready()->add($job);
		}
		$tube->incrementJobCount();

		return $job->getId();
	}

	public function reserve($tube, $timeout = null, $stopWatching = false) {
		$tube = $this->getTube($tube);

		if ($tube->isPaused()) {
			return new NullJob();
		}

		/** @var Job|null $job */
		$job = $tube->ready()->removeFirst();
		if (!$job) {
			return new NullJob();
		}
		$stats = $this->jobStats[$job->getId()];

		$stats->set('state', 'reserved');
		$stats->set('reserves', $stats->reserves() + 1);
		$tube->reserved()->add($job);

		return $job;
	}

	public function kickTube($tube, $num = -1) {
		$tube = $this->getTube($tube);

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

	public function deleteReadyJobs($tube, $num = -1) {
		$tube = $this->getTube($tube);

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
		$tube = $this->getTube($tube);

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
		$tube = $this->getTube($tube);

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
		$tube = $this->getTube($tube);
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
	public function getTube($tube) {
		if (!$this->tubes->containsKey($tube)) {
			$this->tubes[$tube] = new ArrayTube();
		}
		return $this->tubes->get($tube);
	}

	public function __construct() {
		$this->tubes = new ArrayCollection();
		$this->jobStats = new ArrayCollection();
	}

	protected function removeEmptyTube(ArrayTube $tube) {
		if (!$tube->isPaused() && $tube->isEmpty()) {
			$this->tubes->removeElement($tube);
		}
	}

	/** @var ArrayCollection|ArrayTube[] */
	protected $tubes;

	/** @var ArrayCollection|JobStats[] */
	protected $jobStats;

	protected $jobCounter = 0;
}
