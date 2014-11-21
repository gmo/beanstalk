<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job\JobControlInterface;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Common\Collections\ArrayCollection;
use Psr\Log\LoggerAwareInterface;

interface QueueInterface extends TubeControlInterface, JobControlInterface, LoggerAwareInterface {

	/**
	 * Returns the names of all the tubes
	 * @return ArrayCollection
	 */
	public function listTubes();

	/**
	 * Gets the stats for all tubes
	 * @return TubeStats[]|ArrayCollection
	 */
	public function statsAllTubes();

	/**
	 * Returns the stats about the server
	 * @return ServerStats
	 */
	public function statsServer();

	/**
	 * Inspect a job in the system by ID
	 * @param int $jobId
	 * @return \GMO\Beanstalk\Job\Job
	 */
	public function peekJob($jobId);
}
