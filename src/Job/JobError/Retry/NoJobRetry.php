<?php

namespace GMO\Beanstalk\Job\JobError\Retry;

class NoJobRetry implements JobRetryInterface
{
    public function getMaxRetries()
    {
        return 0;
    }
}
