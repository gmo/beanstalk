<?php

namespace GMO\Beanstalk\Job\JobError;

interface HasJobErrorInterface
{
    /**
     * @return JobErrorInterface
     */
    public function getJobError();
}
