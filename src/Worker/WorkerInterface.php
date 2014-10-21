<?php
namespace GMO\Beanstalk\Worker;

use GMO\Beanstalk\Job;
use GMO\Common\ClassNameResolverInterface;

interface WorkerInterface extends ClassNameResolverInterface {

	/**
	 * Return worker name.
	 * @return string
	 */
	public static function getTubeName();

	/**
	 * Return the runner class
	 * @return string class name
	 */
	public static function getRunnerClass();

	/**
	 * Return number of workers to spawn.
	 * @return int
	 */
	public static function getNumberOfWorkers();

	/**
	 * Returns a logger instance for worker
	 * @return \Psr\Log\LoggerInterface
	 */
	public function getLogger();

	/**
	 * Setup worker to run. Called only one time.
	 */
	public function setup();

	/**
	 * Return an array of parameters required for job to continue.
	 * @return array
	 */
	public function getRequiredParams();

	/**
	 * Process each job
	 * @param Job $job
	 */
	public function process($job);

}
