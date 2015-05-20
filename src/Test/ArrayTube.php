<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Beanstalk\Tube\Tube;
use GMO\Beanstalk\Tube\TubeControlInterface;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\DateTime;

/**
 * ArrayTube is an in-memory representation of a beanstalk tube. Used with ArrayQueue.
 * @see \GMO\Beanstalk\Test\ArrayQueue
 */
class ArrayTube extends Tube {

	public function isPaused() {
		if ($this->pauseDelay === 0) {
			return false;
		}

		if ($this->pauseTime > new DateTime("-{$this->pauseDelay} sec")) {
			return true;
		} else {
			$this->pauseDelay = 0;
			return false;
		}
	}

	public function pause($delay) {
		$this->pauseDelay = $delay;
		$this->pauseTime = new DateTime();
		$this->cmdPauseCount++;
	}

	public function getPauseSeconds() {
		if (!$this->isPaused()) {
			return 0;
		}
		return $this->pauseTime->diff(new DateTime())->s;
	}

	public function getPauseTimeLeft() {
		if (!$this->isPaused()) {
			return 0;
		}
		$diff = DateTime::now()->modify("-{$this->pauseDelay} sec")->diff($this->pauseTime);
		return $diff->s;
	}

	public function getStats() {
		return new TubeStats(array(
			'current-jobs-ready' => $this->ready()->count(),
			'current-jobs-reserved' => $this->reserved()->count(),
			'current-jobs-delayed' => $this->delayed()->count(),
			'current-jobs-buried' => $this->buried()->count(),
			'pause' => $this->getPauseSeconds(),
			'pause-time-left' => $this->getPauseTimeLeft(),
		));
	}

	public function isEmpty() {
		return $this->ready->isEmpty() &&
			   $this->reserved->isEmpty() &&
			   $this->delayed->isEmpty() &&
			   $this->buried->isEmpty();
	}

	public function incrementJobCount($count = 1) {
		$this->jobCount += $count;
	}

	public function incrementDeleteCount($count = 1) {
		$this->cmdDeleteCount += $count;
	}

	public function ready() {
		$this->moveDelayedJobs();
		$this->prioritizeJobs();
		return $this->ready;
	}

	public function reserved() {
		return $this->reserved;
	}

	public function delayed() {
		$this->moveDelayedJobs();
		return $this->delayed;
	}

	public function buried() {
		return $this->buried;
	}

	public function __construct($name, TubeControlInterface $queue) {
		parent::__construct($name, $queue);

		$this->ready = new ArrayCollection();
		$this->reserved = new ArrayCollection();
		$this->delayed = new ArrayCollection();
		$this->buried = new ArrayCollection();

		$this->pauseTime = new DateTime();
	}

	protected function prioritizeJobs() {
		$tube = $this->ready;
		$this->ready->sortValues(function(ArrayJob $a, ArrayJob $b) use ($tube) {
			if ($a->getPriority() === $b->getPriority()) {
				return $tube->indexOf($a) > $tube->indexOf($b) ? 1 : -1;
			}
			return $a->getPriority() > $b->getPriority() ? 1 : -1;
		});
	}

	protected function moveDelayedJobs() {
		$nowReady = $this->delayed->filter(function(ArrayJob $job) {
			return !$job->isDelayed();
		});
		$this->ready->merge($nowReady);
		foreach ($nowReady as $job) {
			$this->delayed->removeElement($job);
		}
	}

	/** @var ArrayCollection|ArrayJob[] */
	protected $ready;
	/** @var ArrayCollection|ArrayJob[] */
	protected $reserved;
	/** @var ArrayCollection|ArrayJob[] */
	protected $delayed;
	/** @var ArrayCollection|ArrayJob[] */
	protected $buried;

	/** @var DateTime */
	protected $pauseTime;
	protected $pauseDelay = 0;

	protected $cmdPauseCount = 0;
	protected $cmdDeleteCount = 0;
	protected $jobCount = 0;
}
