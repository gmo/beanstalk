<?php
namespace GMO\Beanstalk\Job\JobError;

use GMO\Beanstalk\Job\JobError\Action\JobActionInterface;
use GMO\Beanstalk\Job\JobError\Delay\JobDelayInterface;
use GMO\Beanstalk\Job\JobError\Retry\JobRetryInterface;

/**
 * This interface tells the Runner/Worker what to
 * do with the job when an Exception is thrown.
 *
 * Exceptions should implement this interface.
 */
interface JobErrorInterface extends JobActionInterface, JobDelayInterface, JobRetryInterface { }
