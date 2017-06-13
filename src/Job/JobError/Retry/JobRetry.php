<?php

namespace GMO\Beanstalk\Job\JobError\Retry;

class JobRetry implements JobRetryInterface
{
    protected $retry;

    /**
     * @param int $retry
     */
    public function __construct($retry)
    {
        $this->retry = intval($retry);
    }

    public function getMaxRetries()
    {
        return $this->retry;
    }
}
