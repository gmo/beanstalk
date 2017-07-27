<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Action;

class BuryJobAction implements JobActionInterface
{
    public function getActionToTake()
    {
        return JobActionInterface::BURY;
    }
}
