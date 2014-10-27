<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

class LinearJobDelay implements JobDelayInterface {

	public function getDelay($numRetries) {
		return $this->delay;
	}

	/**
	 * @param int $delay
	 */
	public function __construct($delay) {
		$this->delay = intval($delay);
	}

	protected $delay;
}
