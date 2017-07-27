<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError;

interface HasJobErrorInterface
{
    /**
     * @return JobErrorInterface
     */
    public function getJobError();
}
