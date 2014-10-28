<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

interface JobDelayInterface {

	/**
	 * Returns the number of seconds the job should be delayed before being retried
	 * @param $numRetries
	 * @return int
	 */
	public function getDelay($numRetries);

	/**
	 * Returns whether to pause the tube or just the job
	 * @return bool
	 */
	public function shouldPauseTube();
}
