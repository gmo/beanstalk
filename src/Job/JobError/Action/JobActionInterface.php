<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Action;

interface JobActionInterface
{
    const BURY = 'bury';
    const DELETE = 'delete';

    /**
     * Returns the action to take on the job
     *
     * @return int
     */
    public function getActionToTake();
}
