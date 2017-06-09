<?php

namespace GMO\Beanstalk\Worker;

use GMO\Beanstalk\Job\JobProducerInterface;
use GMO\Beanstalk\Runner\BaseRunner;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Str;

/**
 * Sets default values for WorkerInterface
 */
abstract class AbstractWorker implements WorkerInterface
{
    /**
     * Shortcut for {@see JobProducerInterface::push} that uses the worker's tube name and ttr
     *
     * @param JobProducerInterface                               $queue
     * @param \GMO\Common\ISerializable|\Traversable|array|mixed $data     Job data
     * @param int|null                                           $priority From 0 (most urgent) to 4294967295 (least
     *                                                                     urgent)
     * @param int|null                                           $delay    Seconds to wait before job becomes ready
     *
     * @return int The new job ID
     */
    public static function pushData(JobProducerInterface $queue, $data, $priority = null, $delay = null)
    {
        return $queue->push(static::getTubeName(), $data, $priority, $delay, static::getTimeToRun());
    }

    public static function getTubeName()
    {
        return Str::className(static::className());
    }

    public static function getRunner()
    {
        return new BaseRunner();
    }

    public static function getNumberOfWorkers()
    {
        return 1;
    }

    public static function getTimeToRun()
    {
        return JobProducerInterface::DEFAULT_TTR;
    }

    public static function getErrorHandlers()
    {
        return new ArrayCollection();
    }

    public static function getRequiredParams()
    {
        return new ArrayCollection();
    }

    public function setup()
    {
    }

    public function onStop()
    {
    }

    public static function className()
    {
        return get_called_class();
    }
}
