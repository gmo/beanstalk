<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Exception\Job;

use Gmo\Beanstalk\Job\JobError\Action\BuryJobAction;
use Gmo\Beanstalk\Job\JobError\Delay\NoJobDelay;
use Gmo\Beanstalk\Job\JobError\Retry\NoJobRetry;

class BuryJobImmediatelyException extends JobException
{
    public function __construct(\Exception $exception)
    {
        parent::__construct($exception, new NoJobDelay(), new NoJobRetry(), new BuryJobAction());
    }
}
