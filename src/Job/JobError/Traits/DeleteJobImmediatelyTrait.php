<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Traits;

use Gmo\Beanstalk\Job\JobError\Action\DeleteJobAction;
use Gmo\Beanstalk\Job\JobError\Delay\NoJobDelay;
use Gmo\Beanstalk\Job\JobError\Retry\NoJobRetry;

trait DeleteJobImmediatelyTrait
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
        return new DeleteJobAction();
    }
}
