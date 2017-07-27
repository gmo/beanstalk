<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Runner\RunOnceRunnerDecorator;
use Gmo\Beanstalk\Worker\RpcWorker;
use Psr\Log\NullLogger;

class UnitTestRpcWorker extends RpcWorker
{
    public static function getRunner()
    {
        return new RunOnceRunnerDecorator(parent::getRunner());
    }

    public static function getLogger()
    {
        return new NullLogger();
    }

    public function process(Job $job)
    {
        $job->setResult(((int) $job['a']) * ((int) $job['b']));
    }
}
