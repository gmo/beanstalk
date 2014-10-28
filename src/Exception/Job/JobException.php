<?php
namespace GMO\Beanstalk\Exception\Job;

use GMO\Beanstalk\Exception\QueueException;
use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\Action\JobActionInterface;
use GMO\Beanstalk\Job\JobError\Delay\JobDelayInterface;
use GMO\Beanstalk\Job\JobError\Delay\NoJobDelay;
use GMO\Beanstalk\Job\JobError\JobErrorInterface;
use GMO\Beanstalk\Job\JobError\Retry\JobRetryInterface;
use GMO\Beanstalk\Job\JobError\Retry\NoJobRetry;

/**
 * Exceptions can be wrapped in this class or extend this class
 * to tell the Runner/Worker what to do with the job
 */
class JobException extends QueueException implements JobErrorInterface {

	public function getDelay($numRetries) {
		return $this->delay->getDelay($numRetries);
	}

	public function shouldPauseTube() {
		return $this->delay->shouldPauseTube();
	}

	public function getMaxRetries() {
		return $this->retry->getMaxRetries();
	}

	public function getActionToTake() {
		return $this->action->getActionToTake();
	}

	public function __toString() {
		return $this->getPrevious() ? $this->getPrevious()->__toString() : parent::__toString();
	}

	public function setDelay(JobDelayInterface $delay) {
		$this->delay = $delay;
		return $this;
	}

	public function setRetry(JobRetryInterface $retry) {
		$this->retry = $retry;
		return $this;
	}

	public function setAction(JobActionInterface $action) {
		$this->action = $action;
		return $this;
	}

	public static function create(\Exception $exception, JobDelayInterface $delay = null, JobRetryInterface $retry = null, JobActionInterface $action = null) {
		return new static($exception, $delay, $retry, $action);
	}

	/**
	 * @param \Exception         $exception
	 * @param JobDelayInterface  $delay
	 * @param JobRetryInterface  $retry
	 * @param JobActionInterface $action
	 */
	public function __construct(\Exception $exception, JobDelayInterface $delay = null, JobRetryInterface $retry = null, JobActionInterface $action = null) {
		parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
		$this->delay = $delay ?: new NoJobDelay();
		$this->retry = $retry ?: new NoJobRetry();
		$this->action = $action ?: new BuryJobAction();
	}

	protected $delay;
	protected $retry;
	protected $action;
}
