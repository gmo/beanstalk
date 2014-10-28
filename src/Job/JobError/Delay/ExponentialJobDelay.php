<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

class ExponentialJobDelay extends LinearJobDelay {

	public function getDelay($numRetries) {
		return pow($this->delay, $numRetries + 1);
	}
}
