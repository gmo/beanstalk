<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Delay;

class NoJobDelay implements JobDelayInterface
{
    public function getDelay($numRetries)
    {
        return 0;
    }

    public function shouldPauseTube()
    {
        return false;
    }
}
