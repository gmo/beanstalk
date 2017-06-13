<?php

namespace Gmo\Beanstalk\Tests\Worker\TestWorkers;

use GMO\Beanstalk\Worker\AbstractWorker;
use Psr\Log\NullLogger;

abstract class AbstractTestWorker extends AbstractWorker
{
    public static function getNumberOfWorkers()
    {
        return 0;
    }

    public static function getLogger()
    {
        return new NullLogger();
    }
}
