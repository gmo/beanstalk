<?php

namespace Gmo\Beanstalk\Job\JobError;

interface HasJobErrorInterface
{
    /**
     * @return JobErrorInterface
     */
    public function getJobError();
}
