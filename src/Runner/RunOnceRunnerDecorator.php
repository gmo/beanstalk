<?php
namespace GMO\Beanstalk\Runner;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;

/**
 * Modifies the runner to only process one job
 */
class RunOnceRunnerDecorator extends RunnerDecorator {

	public function shouldKeepRunning() {
		if (!$this->currentJob instanceof NullJob) {
			return false;
		}
		return parent::shouldKeepRunning();
	}

	public function getJob(Job $previousJob) {
		$job = parent::getJob($previousJob);
		$this->currentJob = $job;
		return $job;
	}

	private $currentJob;
}
