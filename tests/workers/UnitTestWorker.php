<?php

namespace workers;

use GMO\Beanstalk\Job\Job;

class UnitTestWorker extends AbstractTestWorker
{
    public static function getRequiredParams()
    {
        return array("param1", "param2");
    }

    public function process(Job $job)
    {
        $job->setResult($job->getData()->getValues());
    }
}
