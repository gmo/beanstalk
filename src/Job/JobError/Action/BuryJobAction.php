<?php

namespace Gmo\Beanstalk\Job\JobError\Action;

class BuryJobAction implements JobActionInterface
{
    public function getActionToTake()
    {
        return JobActionInterface::BURY;
    }
}
