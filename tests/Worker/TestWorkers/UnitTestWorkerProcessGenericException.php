<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use Gmo\Beanstalk\Job\Job;

class UnitTestWorkerProcessGenericException extends AbstractTestWorker
{
    public static function getRequiredParams()
    {
        return ['param1', 'param2'];
    }

    public function process(Job $job)
    {
        throw new \Exception('The process fails');
    }
}
