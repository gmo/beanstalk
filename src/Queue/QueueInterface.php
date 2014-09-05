<?php
namespace GMO\Beanstalk\Queue;

use Pheanstalk\Job;
use Psr\Log\LoggerAwareInterface;

interface QueueInterface extends LoggerAwareInterface {

	/**
	 * Push a job to a tube
	 * @param string $tube       tube name
	 * @param array  $data       job data
	 * @param bool   $jsonEncode Optional. Default true.
	 */
	public function push($tube, $data, $jsonEncode = true);

	/**
	 * Reserves a job from the specified tube or false if error or timeout
	 * @param string   $tube
	 * @param int|null $timeout
	 * @return Job|false
	 */
	public function getJob($tube, $timeout = null);

	/**
	 * Buries a job
	 * @param Job $job
	 */
	public function buryJob($job);

	/**
	 * Deletes a job
	 * @param Job $job
	 */
	public function deleteJob($job);

	/**
	 * Returns the names of all the tubes
	 * @return array
	 */
	public function listTubes();

	/**
	 * Gets the stats for the given tube
	 * or all tubes if no tube is specified.
	 * @param string $tube
	 * @return array
	 */
	public function stats($tube = null);

	/**
	 * Kicks all jobs in a given tube
	 * @param string $tube
	 */
	public function kickJobs($tube);

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

	public function readyJobsFromTube($tube);


}
