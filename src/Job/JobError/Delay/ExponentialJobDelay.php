<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

class ExponentialJobDelay implements JobDelayInterface {

	public function getDelay($numRetries) {
		return pow($this->delay, $numRetries + 1);
	}

	/**
	 * @param int $delay
	 */
	public function __construct($delay) {
		$this->delay = intval($delay);
	}

	protected $delay;
}
