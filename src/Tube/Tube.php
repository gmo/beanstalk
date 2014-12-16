<?php
namespace GMO\Beanstalk\Tube;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Beanstalk\Queue\TubeControlInterface;

class Tube {

	/**
	 * Reserves a job
	 * @param int|null $timeout
	 * @param bool     $stopWatching Stop watching the tube after reserving the job
	 * @return Job
	 */
	public function reserve($timeout = null, $stopWatching = false) {
		return $this->queue->reserve($this->name, $timeout, $stopWatching);
	}

	/**
	 * Kicks jobs
	 * Buried jobs will be kicked before delayed jobs
	 * @param int $num Number of jobs to kick, -1 is all
	 * @return int number of jobs deleted
	 */
	public function kick($num = -1) {
		return $this->queue->kickTube($this->name, $num);
	}

	/**
	 * Inspect the next ready job
	 * @return Job
	 */
	public function peekReady() {
		return $this->queue->peekReady($this->name);
	}

	/**
	 * Inspect the next buried job
	 * @return Job
	 */
	public function peekBuried() {
		return $this->queue->peekBuried($this->name);
	}

	/**
	 * Inspect the next delayed job
	 * @return Job
	 */
	public function peekDelayed() {
		return $this->queue->peekDelayed($this->name);
	}

	/**
	 * Delete jobs in the ready state
	 * @param int $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteReadyJobs($num = -1) {
		$this->queue->deleteReadyJobs($this->name, $num);
	}

	/**
	 * Delete jobs in the buried state
	 * @param int $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteBuriedJobs($num = -1) {
		$this->queue->deleteBuriedJobs($this->name, $num);
	}

	/**
	 * Delete jobs in the delayed state
	 * @param int $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteDelayedJobs($num = -1) {
		$this->queue->deleteDelayedJobs($this->name, $num);
	}

	/**
	 * Temporarily prevent jobs being reserved
	 * @param int $delay Seconds before jobs may be reserved
	 */
	public function pause($delay) {
		$this->queue->pause($this->name, $delay);
	}

	/**
	 * Gets the tube's stats
	 * @return TubeStats
	 */
	public function stats() {
		return $this->queue->statsTube($this->name);
	}

	public function name() {
		return $this->name;
	}

	public function __construct($name, TubeControlInterface $queue) {
		$this->name = $name;
		$this->queue = $queue;
	}

	protected $name;
	/** @var TubeControlInterface */
	protected $queue;
}
