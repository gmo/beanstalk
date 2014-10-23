<?php
namespace GMO\Beanstalk\Runner;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Worker\WorkerInterface;
use GMO\Common\ClassNameResolverInterface;

interface RunnerInterface extends ClassNameResolverInterface {

	public function setup(QueueInterface $queue, WorkerInterface $worker);

	public function run();

	public function preProcessJob(Job $job);

	/**
	 * Validates current job
	 * @param Job $job
	 * @return bool
	 */
	public function validateJob(Job $job);

	public function processJob(Job $job);

	public function postProcessJob(Job $job);

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
