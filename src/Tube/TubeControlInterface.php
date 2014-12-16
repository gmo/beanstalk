<?php
namespace GMO\Beanstalk\Tube;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\JobProducerInterface;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Queue\Response\TubeStats;

interface TubeControlInterface extends JobProducerInterface {

	/**
	 * Reserves a job from the specified tube
	 * @param string   $tube
	 * @param int|null $timeout
	 * @param bool     $stopWatching Stop watching the tube after reserving the job
	 * @return Job
	 */
	public function reserve($tube, $timeout = null, $stopWatching = false);

	/**
	 * Kicks all jobs in a given tube.
	 * Buried jobs will be kicked before delayed jobs
	 * @param string $tube
 	 * @param int    $num Number of jobs to kick, -1 is all
	 * @return int number of jobs deleted
	 */
	public function kickTube($tube, $num = -1);

	/**
	 * Inspect the next ready job in the specified tube
	 * @param string $tube
	 * @return Job|NullJob
	 */
	public function peekReady($tube);

	/**
	 * Inspect the next buried job in the specified tube
	 * @param string $tube
	 * @return Job|NullJob
	 */
	public function peekBuried($tube);

	/**
	 * Inspect the next delayed job in the specified tube
	 * @param string $tube
	 * @return Job|NullJob
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
