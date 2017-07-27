<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job\JobError\Delay;

class ExponentialJobDelay extends LinearJobDelay
{
    public function getDelay($numRetries)
    {
        return $this->delay ** ($numRetries + 1);
    }
}
