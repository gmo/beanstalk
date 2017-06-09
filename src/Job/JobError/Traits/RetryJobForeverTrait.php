<?php

namespace GMO\Beanstalk\Job\JobError\Traits;

use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\Retry\InfiniteJobRetry;

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
