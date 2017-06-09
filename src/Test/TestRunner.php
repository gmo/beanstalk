<?php

namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Runner\BaseRunner;
use GMO\Beanstalk\Worker\WorkerInterface;

class TestRunner extends BaseRunner
{
    protected $currentJob;

    public function __construct(QueueInterface $queue, WorkerInterface $worker)
    {
        $this->setup($queue, $worker);
        $this->stopRunning();
    }

    /**
     * @return Job
     */
    public function run()
    {
        parent::run();

        return $this->currentJob;
    }

    public function getJob(Job $previousJob)
    {
        $job = parent::getJob($previousJob);
        $this->currentJob = $job;

        return $job;
    }

    protected function attachSignalHandler()
    {
    }

    protected function checkForTerminationSignal()
    {
    }
}
