<?php
namespace GMO\Beanstalk\Job\JobError\Delay;

class HourlyJobDelay extends LinearJobDelay {

	/**
	 * @param int  $delay in hours
	 * @param bool $pauseTube
	 */
	public function __construct($delay = 1, $pauseTube = false) {
		parent::__construct($delay * 3600, $pauseTube);
	}
}
