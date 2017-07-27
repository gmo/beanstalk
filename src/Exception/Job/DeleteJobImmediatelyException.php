<?php

namespace Gmo\Beanstalk\Exception\Job;

use Gmo\Beanstalk\Job\JobError\Action\DeleteJobAction;
use Gmo\Beanstalk\Job\JobError\Delay\NoJobDelay;
use Gmo\Beanstalk\Job\JobError\Retry\NoJobRetry;

class DeleteJobImmediatelyException extends JobException
{
    public function __construct(\Exception $exception)
    {
        parent::__construct($exception, new NoJobDelay(), new NoJobRetry(), new DeleteJobAction());
    }
}
