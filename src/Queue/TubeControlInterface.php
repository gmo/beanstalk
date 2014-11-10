<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\Response\TubeStats;
use Pheanstalk\PheanstalkInterface;

interface TubeControlInterface {

	const DEFAULT_PRIORITY = PheanstalkInterface::DEFAULT_PRIORITY;
	const DEFAULT_DELAY = PheanstalkInterface::DEFAULT_DELAY;
	const DEFAULT_TTR = PheanstalkInterface::DEFAULT_TTR;

	/**
	 * Pushes a job to the specified tube
	 * @param string   $tube     Tube name
	 * @param \GMO\Common\ISerializable|\Traversable|array|mixed $data Job data
	 * @param int|null $priority From 0 (most urgent) to 4294967295 (least urgent)
	 * @param int|null $delay    Seconds to wait before job becomes ready
	 * @param int|null $ttr      Time To Run: seconds a job can be reserved for
	 * @return int The new job ID
	 */
	public function push($tube, $data, $priority = null, $delay = null, $ttr = null);

	/**
	 * Reserves a job from the specified tube or false if error or timeout
	 * @param string   $tube
	 * @param int|null $timeout
	 * @param bool     $stopWatching Stop watching the tube after reserving the job
	 * @return Job
	 */
	public function reserve($tube, $timeout = null, $stopWatching = false);

	/**
	 * Kicks all jobs in a given tube
	 * @param string $tube
	 */
	public function kickTube($tube);

	/**
	 * Inspect the next ready job in the specified tube
	 * @param string $tube
	 * @return Job
	 */
	public function peekReady($tube);

	/**
	 * Inspect the next buried job in the specified tube
	 * @param string $tube
	 * @return Job
	 */
	public function peekBuried($tube);

	/**
	 * Inspect the next delayed job in the specified tube
	 * @param string $tube
	 * @return Job
	 */
	public function peekDelayed($tube);

	/**
	 * Deletes all ready jobs in a given tube
	 * @param string $tube
	 * @param int    $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteReadyJobs($tube, $num = -1);

	/**
	 * Deletes all buried jobs in a given tube
	 * @param string $tube
	 * @param int    $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteBuriedJobs($tube, $num = -1);

	/**
	 * Deletes all delayed jobs in a given tube
	 * @param string $tube
	 * @param int    $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteDelayedJobs($tube, $num = -1);

	/**
	 * Temporarily prevent jobs being reserved from the given tube
	 *
	 * @param string $tube  The tube to pause
	 * @param int    $delay Seconds before jobs may be reserved from this queue.
	 */
	public function pause($tube, $delay);

	/**
	 * Gets the stats for the given tube
	 * @param string $tube
	 * @return TubeStats
	 */
	public function statsTube($tube);
}
