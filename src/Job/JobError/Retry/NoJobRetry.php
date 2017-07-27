<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Retry;

class NoJobRetry implements JobRetryInterface
{
    public function getMaxRetries()
    {
        return 0;
    }
}
