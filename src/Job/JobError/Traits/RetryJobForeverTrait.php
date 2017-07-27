<?php

namespace Gmo\Beanstalk\Job\JobError\Traits;

use Gmo\Beanstalk\Job\JobError\Action\BuryJobAction;
use Gmo\Beanstalk\Job\JobError\Retry\InfiniteJobRetry;

trait RetryJobForeverTrait
{
    public function getMaxRetries()
    {
        return new InfiniteJobRetry();
    }

    public function getActionToTake()
    {
        return new BuryJobAction();
    }
}
