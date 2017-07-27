<?php

namespace Gmo\Beanstalk\Runner;

use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\UnserializableJob;

/**
 * Classes can extend this one to handle jobs with unserializable data
 */
abstract class JobConverterRunner extends RunnerDecorator
{
    /**
     * Manually unserialize the job data
     *
     * @param mixed $jobData
     *
     * @return mixed
     */
    abstract protected function convertJobData($jobData);

    public function preProcessJob(Job $job)
    {
        if ($job instanceof UnserializableJob) {
            $data = $this->convertJobData($job->getData());
            $job = new Job($job->getId(), $data, $this->queue);
        }

        return parent::preProcessJob($job);
    }
}
