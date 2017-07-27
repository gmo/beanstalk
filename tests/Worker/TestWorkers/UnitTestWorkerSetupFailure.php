<?php

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use Gmo\Beanstalk\Job\Job;

class UnitTestWorkerSetupFailure extends AbstractTestWorker
{
    public static function tubeName()
    {
        return "UnitTestWorker";
    }

    public function setup()
    {
        throw new \Exception("Setup function failed!");
    }

    public function process(Job $job)
    {
    }
}
