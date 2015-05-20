<?php
namespace GMO\Beanstalk\Job\JobError;

use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\Action\DeleteJobAction;
use GMO\Beanstalk\Job\JobError\Delay\ExponentialJobDelay;
use GMO\Beanstalk\Job\JobError\Delay\HourlyJobDelay;
use GMO\Beanstalk\Job\JobError\Delay\LinearJobDelay;
use GMO\Beanstalk\Job\JobError\Delay\NoJobDelay;
use GMO\Beanstalk\Job\JobError\Retry\InfiniteJobRetry;
use GMO\Beanstalk\Job\JobError\Retry\JobRetry;
use GMO\Beanstalk\Job\JobError\Retry\NoJobRetry;

/**
 * While JobError allows flexibility with the components used, it has verbose syntax.
 * FluentJobError helps this by giving shortcuts for core components.
 */
class FluentJobError extends JobError
{
	public function delay($seconds = 60, $pauseTube = false) {
		$this->setDelay(new LinearJobDelay($seconds, $pauseTube));
		return $this;
	}

	public function hourlyDelay($hours = 1, $pauseTube = false) {
		$this->setDelay(new HourlyJobDelay($hours, $pauseTube));
		return $this;
	}

	public function exponentialDelay($delay, $pauseTube = false) {
		$this->setDelay(new ExponentialJobDelay($delay, $pauseTube));
		return $this;
	}

	public function noDelay() {
		$this->setDelay(new NoJobDelay());
		return $this;
	}

	public function retry($num) {
		$this->setRetry(new JobRetry($num));
		return $this;
	}

	public function noRetry() {
		$this->setRetry(new NoJobRetry());
		return $this;
	}

	public function alwaysRetry() {
		$this->setRetry(new InfiniteJobRetry());
		return $this;
	}

	public function bury() {
		$this->setAction(new BuryJobAction());
		return $this;
	}

	public function delete() {
		$this->setAction(new DeleteJobAction());
		return $this;
	}

	public function deleteImmediately() {
		return $this->noRetry()->delete();
	}
}
