<?php

namespace GMO\Beanstalk\Job\JobError\Traits;

use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\Delay\NoJobDelay;
use GMO\Beanstalk\Job\JobError\Retry\NoJobRetry;

trait BuryJobImmediatelyTrait
{
    public function getDelay($numRetries)
    {
        return new NoJobDelay();
    }

    public function getMaxRetries()
    {
        return new NoJobRetry();
    }

    public function getActionToTake()
    {
        return new BuryJobAction();
    }
}
