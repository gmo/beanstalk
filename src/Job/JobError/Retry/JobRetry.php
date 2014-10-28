<?php
namespace GMO\Beanstalk\Job\JobError\Retry;

class JobRetry implements JobRetryInterface {

	public function getMaxRetries() {
		return $this->retry;
	}

	/**
	 * @param int $retry
	 */
	public function __construct($retry) {
		$this->retry = intval($retry);
	}

	protected $retry;
}
