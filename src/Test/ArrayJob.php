<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\JobControlInterface;
use GMO\Common\DateTime;

/**
 * ArrayJob is used by {@see ArrayQueue} to
 * determine if the job is still delayed and the priority
 * @see ArrayQueue
 */
class ArrayJob extends Job {

	public function isDelayed() {
		if ($this->delay === 0) {
			return false;
		}

		if ($this->delayTime > new DateTime("-{$this->delay} sec")) {
			return true;
		} else {
			$this->delay = 0;
			return false;
		}
	}

	public function setDelay($delay) {
		$this->delay = intval($delay);
		$this->delayTime = new DateTime();
	}

	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @param int $priority
	 */
	public function setPriority($priority) {
		$this->priority = intval($priority);
	}

	public function resetHandled() {
		$this->handled = false;
	}

	public function __construct($id, $data, JobControlInterface $queue) {
		parent::__construct($id, $data, $queue);
		$this->delayTime = new DateTime();
	}

	protected $delayTime;
	protected $delay = 0;
	protected $priority = 0;
}
