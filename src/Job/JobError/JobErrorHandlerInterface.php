<?php

namespace Gmo\Beanstalk\Job\JobError;

use Exception;

/**
 * A list of these can be added to a worker which are
 * called when an exception within the worker is thrown.
 */
interface JobErrorHandlerInterface
{
    /**
     * Tell the worker how to handle the exception
     * by returning a JobErrorInterface
     *
     * @param Exception $ex
     *
     * @return JobErrorInterface|null
     */
    public function handle(Exception $ex);
}
