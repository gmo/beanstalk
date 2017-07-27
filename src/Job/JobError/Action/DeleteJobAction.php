<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Action;

class DeleteJobAction implements JobActionInterface
{
    public function getActionToTake()
    {
        return JobActionInterface::DELETE;
    }
}
