<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use Gmo\Beanstalk\Exception\Job\DeleteJobImmediatelyException;
use Gmo\Beanstalk\Job\Job;

class UnitTestWorkerProcessJobError extends AbstractTestWorker
{
    public static function getRequiredParams()
    {
        return array("param1", "param2");
    }

    public function process(Job $job)
    {
        throw new DeleteJobImmediatelyException(new \Exception("The process fails"));
    }
}
