<?php

namespace GMO\Beanstalk\Job\JobError;

use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\Action\JobActionInterface;
use GMO\Beanstalk\Job\JobError\Delay\JobDelayInterface;
use GMO\Beanstalk\Job\JobError\Delay\NoJobDelay;
use GMO\Beanstalk\Job\JobError\Retry\JobRetryInterface;
use GMO\Beanstalk\Job\JobError\Retry\NoJobRetry;

class JobError implements JobErrorInterface
{
    protected $delay;
    protected $retry;
    protected $action;

    public function __construct(
        JobDelayInterface $delay = null,
        JobRetryInterface $retry = null,
        JobActionInterface $action = null
    ) {
        $this->delay = $delay ?: new NoJobDelay();
        $this->retry = $retry ?: new NoJobRetry();
        $this->action = $action ?: new BuryJobAction();
    }

    public static function create(
        JobDelayInterface $delay = null,
        JobRetryInterface $retry = null,
        JobActionInterface $action = null
    ) {
        return new static($delay, $retry, $action);
    }

    public function getDelay($numRetries)
    {
        return $this->delay->getDelay($numRetries);
    }

    public function shouldPauseTube()
    {
        return $this->delay->shouldPauseTube();
    }

    public function getMaxRetries()
    {
        return $this->retry->getMaxRetries();
    }

    public function getActionToTake()
    {
        return $this->action->getActionToTake();
    }

    public function setDelay(JobDelayInterface $delay)
    {
        $this->delay = $delay;

        return $this;
    }

    public function setRetry(JobRetryInterface $retry)
    {
        $this->retry = $retry;

        return $this;
    }

    public function setAction(JobActionInterface $action)
    {
        $this->action = $action;

        return $this;
    }
}
