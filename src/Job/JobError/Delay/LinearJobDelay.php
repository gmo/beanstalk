<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Delay;

class LinearJobDelay implements JobDelayInterface
{
    protected $delay;
    protected $pause;

    /**
     * @param int  $delay in seconds
     * @param bool $pauseTube
     */
    public function __construct($delay, $pauseTube = false)
    {
        $this->delay = intval($delay);
        $this->pause = $pauseTube;
    }

    public function getDelay($numRetries)
    {
        return $this->delay;
    }

    public function shouldPauseTube()
    {
        return $this->pause;
    }
}
