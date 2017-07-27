<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError;

use Gmo\Beanstalk\Job\JobError\Action\JobActionInterface;
use Gmo\Beanstalk\Job\JobError\Delay\JobDelayInterface;
use Gmo\Beanstalk\Job\JobError\Retry\JobRetryInterface;

/**
 * This interface tells the Runner/Worker what to
 * do with the job when an Exception is thrown.
 *
 * Exceptions should implement this interface.
 */
interface JobErrorInterface extends JobActionInterface, JobDelayInterface, JobRetryInterface
{
}
