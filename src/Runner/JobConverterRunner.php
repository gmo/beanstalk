<?php
namespace GMO\Beanstalk\Runner;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\UnserializableJob;
use GMO\Common\Collections\ArrayCollection;

/**
 * Classes can extend this one to handle jobs with unserializable data
 */
abstract class JobConverterRunner extends RunnerDecorator {

	/**
	 * Manually unserialize the job data
	 * @param ArrayCollection|mixed $jobData
	 * @return mixed
	 */
	abstract protected function convertJobData($jobData);

	public function preProcessJob(Job $job) {
		if ($job instanceof UnserializableJob) {
			$data = $this->convertJobData($job->getData());
			$job = new Job($job->getId(), $data, $this->queue);
		}
		parent::preProcessJob($job);
	}
}