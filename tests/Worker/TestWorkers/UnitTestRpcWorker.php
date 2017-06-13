<?php

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Runner\RunOnceRunnerDecorator;
use GMO\Beanstalk\Worker\RpcWorker;
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
        $job->setResult(intval($job['a']) * intval($job['b']));
    }
}
