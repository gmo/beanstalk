<?php

namespace Gmo\Beanstalk\Job\JobError\Retry;

class InfiniteJobRetry implements JobRetryInterface
{
    public function getMaxRetries()
    {
        return PHP_INT_MAX;
    }
}
