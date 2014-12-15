<?php
namespace GMO\Beanstalk\Runner;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Worker\WorkerInterface;
use GMO\Common\ClassNameResolverInterface;

interface RunnerInterface extends ClassNameResolverInterface {

	public function setup(QueueInterface $queue, WorkerInterface $worker);

	public function run();

	public function processJob(Job $job);

	/**
	 * @param Job $job
	 * @return Job
	 */
	public function preProcessJob(Job $job);

	/**
	 * Validates current job
	 * @param Job $job
	 * @return bool
	 */
	public function validateJob(Job $job);

	public function postProcessJob(Job $job);

	public function setupWorker(WorkerInterface $worker);

	/**
	 * @param Job $previousJob
	 * @return Job
	 */
	public function getJob(Job $previousJob);

	/**
	 * Should the runner keep processing jobs?
	 * @return bool
	 */
	public function shouldKeepRunning();

	/**
	 * Tell the runner to stop running
	 */
	public function stopRunning();
}
