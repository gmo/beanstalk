<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job\JobControlInterface;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Beanstalk\Tube\Tube;
use GMO\Beanstalk\Tube\TubeCollection;
use GMO\Beanstalk\Tube\TubeControlInterface;
use GMO\Common\Collections\ArrayCollection;
use Psr\Log\LoggerAwareInterface;

interface QueueInterface extends TubeControlInterface, JobControlInterface, LoggerAwareInterface {

	/**
	 * Gets a tube by name
	 * @param string $name
	 * @return Tube
	 */
	public function tube($name);

	/**
	 * Gets a list of all the tubes
	 * @return TubeCollection
	 */
	public function tubes();

	/**
	 * Returns the names of all the tubes
	 * @return ArrayCollection
	 *
	 * @deprecated Use {@see tubes} instead
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
