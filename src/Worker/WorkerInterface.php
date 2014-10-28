<?php
namespace GMO\Beanstalk\Worker;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Runner\RunnerInterface;
use GMO\Common\ClassNameResolverInterface;

interface WorkerInterface extends ClassNameResolverInterface {

	/**
	 * The tube name the worker should pull jobs from.
	 * @return string
	 */
	public static function getTubeName();

	/**
	 * Returns the runner
	 * @return RunnerInterface
	 */
	public static function getRunner();

	/**
	 * Return number of workers to spawn.
	 * @return int
	 */
	public static function getNumberOfWorkers();

	/**
	 * Return the number of seconds a job for
	 * this worker should be allowed to run.
	 * @return int seconds
	 */
	public static function getTimeToRun();

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

	/**
	 * Called when the worker is stopped
	 */
	public function onStop();
}
