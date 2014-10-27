<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

interface JobDelayInterface {

	/**
	 * Returns the number of seconds the job should be delayed before being retried
	 * @param $numRetries
	 * @return int
	 */
	public function getDelay($numRetries);
}
