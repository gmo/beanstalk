<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use Gmo\Beanstalk\Job\Job;

class UnitTestWorker extends AbstractTestWorker
{
    public static function getRequiredParams()
    {
        return ['param1', 'param2'];
    }

    public function process(Job $job)
    {
        $job->setResult($job->getData()->values());
    }
}
