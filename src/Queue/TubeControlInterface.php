<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job;
use GMO\Beanstalk\Queue\Response\TubeStats;

interface TubeControlInterface {

	/**
	 * Reserves a job from the specified tube or false if error or timeout
	 * @param string   $tube
	 * @param int|null $timeout
	 * @return Job|false
	 */
	public function reserve($tube, $timeout = null);

	/**
	 * Kicks all jobs in a given tube
	 * @param string $tube
	 */
	public function kickTube($tube);

	/**
	 * Deletes all ready jobs in a given tube
	 * @param string $tube
	 */
	public function deleteReadyJobs($tube);

	/**
	 * Deletes all buried jobs in a given tube
	 * @param string $tube
	 */
	public function deleteBuriedJobs($tube);

	/**
	 * Deletes all delayed jobs in a given tube
	 * @param string $tube
	 */
	public function deleteDelayedJobs($tube);

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
