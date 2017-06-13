<?php

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use GMO\Beanstalk\Job\Job;

class NullWorker extends AbstractTestWorker
{
    public static function getNumberOfWorkers()
    {
        return 3;
    }

    public function process(Job $job)
    {
    }
}
