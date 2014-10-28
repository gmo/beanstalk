<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

class LinearJobDelay implements JobDelayInterface {

	public function getDelay($numRetries) {
		return $this->delay;
	}

	public function shouldPauseTube() {
		return $this->pause;
	}

	/**
	 * @param int  $delay
	 * @param bool $pauseTube
	 */
	public function __construct($delay, $pauseTube = false) {
		$this->delay = intval($delay);
		$this->pause = $pauseTube;
	}

	protected $delay;
	protected $pause;
}
