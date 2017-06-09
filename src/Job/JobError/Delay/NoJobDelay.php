<?php

namespace GMO\Beanstalk\Job\JobError\Delay;

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
