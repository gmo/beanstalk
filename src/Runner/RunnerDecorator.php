<?php

namespace GMO\Beanstalk\Runner;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Worker\WorkerInterface;

/**
 * This class is the base for decorating runners.
 *
 * NOTE: {@see RunnerDecorator::run run()} and {@see RunnerDecorator::processJob processJob()}
 *    do not call the previous runner because they call the other methods,
 *    which would not call the wrapped runner's method.
 */
abstract class RunnerDecorator extends BaseRunner
{
    /** @var RunnerInterface */
    protected $runner;

    public function __construct(RunnerInterface $runner)
    {
        $this->runner = $runner;
    }

    public function setup(QueueInterface $queue, WorkerInterface $worker)
    {
        parent::setup($queue, $worker);
        $this->runner->setup($queue, $worker);
    }

    public function preProcessJob(Job $job)
    {
        return $this->runner->preProcessJob($job);
    }

    public function validateJob(Job $job)
    {
        return $this->runner->validateJob($job);
    }

    public function postProcessJob(Job $job)
    {
        $this->runner->postProcessJob($job);
    }

    public function setupWorker(WorkerInterface $worker)
    {
        $this->runner->setupWorker($worker);
    }

    public function getJob(Job $previousJob)
    {
        return $this->runner->getJob($previousJob);
    }

    public function shouldKeepRunning()
    {
        return $this->runner->shouldKeepRunning();
    }

    public function stopRunning()
    {
        $this->runner->stopRunning();
    }

    protected function attachSignalHandler()
    {
    }

    protected function checkForTerminationSignal()
    {
        throw new \LogicException();
    }
}
