<?php

namespace Gmo\Beanstalk\Runner;

use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\NullJob;

/**
 * Modifies the runner to process jobs until there are no more and then stop.
 */
class RunOnceRunnerDecorator extends RunnerDecorator
{
    private $currentJob;

    public function shouldKeepRunning()
    {
        if ($this->currentJob instanceof NullJob) {
            return false;
        }

        return parent::shouldKeepRunning();
    }

    public function getJob(Job $previousJob)
    {
        $job = parent::getJob($previousJob);
        $this->currentJob = $job;

        return $job;
    }
}
