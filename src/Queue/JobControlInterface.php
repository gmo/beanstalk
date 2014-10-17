<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job;
use GMO\Beanstalk\Queue\Response\JobStats;

interface JobControlInterface {

	/**
	 * @param Job $job
	 * @param int $priority
	 * @param int $delay
	 */
	public function release(Job $job, $priority = null, $delay = null);

	/**
	 * Buries a job
	 * @param Job $job
	 * @param int $priority
	 */
	public function bury(Job $job, $priority = null);

	/**
	 * Deletes a job
	 * @param Job $job
	 */
	public function delete(Job $job);

	/**
	 * If the given job exists and is in a buried or delayed state,
	 * it will be moved to the ready queue of the the same tube
	 * where it currently belongs.
	 *
	 * @param Job $job
	 */
	public function kickJob($job);

	/**
	 * Gives statistical information about the specified job if it exists.
	 *
	 * @param Job|int $job
	 * @return JobStats
	 */
	public function statsJob($job);

	/**
	 * Allows a worker to request more time to work on a job.
	 *
	 * This is useful for jobs that potentially take a long time, but you still want
	 * the benefits of a TTR pulling a job away from an unresponsive worker.  A worker
	 * may periodically tell the server that it's still alive and processing a job
	 * (e.g. it may do this on DEADLINE_SOON).
	 *
	 * @param Job $job
	 */
	public function touch(Job $job);
}
