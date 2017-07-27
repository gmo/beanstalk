<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Retry;

interface JobRetryInterface
{
    /**
     * Returns the max number of times to retry a job.
     *
     * @return int
     */
    public function getMaxRetries();
}
