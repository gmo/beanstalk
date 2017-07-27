<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Exception\Job;

use Gmo\Beanstalk\Exception\QueueException;
use Gmo\Beanstalk\Job\JobError\Action\BuryJobAction;
use Gmo\Beanstalk\Job\JobError\Action\JobActionInterface;
use Gmo\Beanstalk\Job\JobError\Delay\JobDelayInterface;
use Gmo\Beanstalk\Job\JobError\Delay\NoJobDelay;
use Gmo\Beanstalk\Job\JobError\JobErrorInterface;
use Gmo\Beanstalk\Job\JobError\Retry\JobRetryInterface;
use Gmo\Beanstalk\Job\JobError\Retry\NoJobRetry;

/**
 * Exceptions can be wrapped in this class or extend this class
 * to tell the Runner/Worker what to do with the job.
 */
class JobException extends QueueException implements JobErrorInterface
{
    protected $delay;
    protected $retry;
    protected $action;

    /**
     * @param \Exception         $exception
     * @param JobDelayInterface  $delay
     * @param JobRetryInterface  $retry
     * @param JobActionInterface $action
     */
    public function __construct(
        \Exception $exception,
        JobDelayInterface $delay = null,
        JobRetryInterface $retry = null,
        JobActionInterface $action = null
    ) {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
        $this->delay = $delay ?: new NoJobDelay();
        $this->retry = $retry ?: new NoJobRetry();
        $this->action = $action ?: new BuryJobAction();
    }

    public static function create(
        \Exception $exception,
        JobDelayInterface $delay = null,
        JobRetryInterface $retry = null,
        JobActionInterface $action = null
    ) {
        return new static($exception, $delay, $retry, $action);
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

    public function __toString()
    {
        return $this->getPrevious() ? $this->getPrevious()->__toString() : parent::__toString();
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
