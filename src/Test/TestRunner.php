<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Test;

use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Queue\QueueInterface;
use Gmo\Beanstalk\Runner\BaseRunner;
use Gmo\Beanstalk\Worker\WorkerInterface;

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
