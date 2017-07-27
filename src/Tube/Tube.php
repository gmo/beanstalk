<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tube;

use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Queue\Response\TubeStats;

class Tube
{
    /** @var string */
    protected $name;
    /** @var TubeControlInterface */
    protected $queue;

    /**
     * Tube Constructor.
     *
     * @param string               $name
     * @param TubeControlInterface $queue
     */
    public function __construct($name, TubeControlInterface $queue)
    {
        $this->name = $name;
        $this->queue = $queue;
    }

    /**
     * Pushes a job to this tube.
     *
     * @param mixed    $data     Job data (needs to be serializable)
     * @param int|null $priority From 0 (most urgent) to 4294967295 (least urgent)
     * @param int|null $delay    Seconds to wait before job becomes ready
     * @param int|null $ttr      Time To Run: seconds a job can be reserved for
     *
     * @return int The new job ID
     */
    public function push($data, $priority = null, $delay = null, $ttr = null)
    {
        return $this->queue->push($this->name, $data, $priority, $delay, $ttr);
    }

    /**
     * Reserves a job.
     *
     * @param int|null $timeout
     * @param bool     $stopWatching Stop watching the tube after reserving the job
     *
     * @return Job
     */
    public function reserve($timeout = null, $stopWatching = false)
    {
        return $this->queue->reserve($this->name, $timeout, $stopWatching);
    }

    /**
     * Kicks jobs
     * Buried jobs will be kicked before delayed jobs.
     *
     * @param int $num Number of jobs to kick, -1 is all
     *
     * @return int number of jobs deleted
     */
    public function kick($num = -1)
    {
        return $this->queue->kickTube($this->name, $num);
    }

    /**
     * Inspect the next ready job.
     *
     * @return Job
     */
    public function peekReady()
    {
        return $this->queue->peekReady($this->name);
    }

    /**
     * Inspect the next buried job.
     *
     * @return Job
     */
    public function peekBuried()
    {
        return $this->queue->peekBuried($this->name);
    }

    /**
     * Inspect the next delayed job.
     *
     * @return Job
     */
    public function peekDelayed()
    {
        return $this->queue->peekDelayed($this->name);
    }

    /**
     * Delete jobs in the ready state.
     *
     * @param int $num Number of jobs to delete, -1 is all
     *
     * @return int number of jobs deleted
     */
    public function deleteReadyJobs($num = -1)
    {
        return $this->queue->deleteReadyJobs($this->name, $num);
    }

    /**
     * Delete jobs in the buried state.
     *
     * @param int $num Number of jobs to delete, -1 is all
     *
     * @return int number of jobs deleted
     */
    public function deleteBuriedJobs($num = -1)
    {
        return $this->queue->deleteBuriedJobs($this->name, $num);
    }

    /**
     * Delete jobs in the delayed state.
     *
     * @param int $num Number of jobs to delete, -1 is all
     *
     * @return int number of jobs deleted
     */
    public function deleteDelayedJobs($num = -1)
    {
        return $this->queue->deleteDelayedJobs($this->name, $num);
    }

    /**
     * Temporarily prevent jobs being reserved.
     *
     * @param int $delay Seconds before jobs may be reserved
     */
    public function pause($delay)
    {
        $this->queue->pause($this->name, $delay);
    }

    /**
     * Is the tube currently paused?
     *
     * @return bool
     */
    public function isPaused()
    {
        return $this->stats()->pauseTimeLeft() === 0;
    }

    /**
     * Does the tube have any jobs in any state?
     *
     * @return bool
     */
    public function isEmpty()
    {
        $stats = $this->stats();

        return $stats->readyJobs() === 0 &&
            $stats->reservedJobs() === 0 &&
            $stats->delayedJobs() === 0 &&
            $stats->buriedJobs() === 0;
    }

    /**
     * Gets the tube's stats.
     *
     * @return TubeStats
     */
    public function stats()
    {
        return $this->queue->statsTube($this->name);
    }

    /**
     * Gets the name of this tube.
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->name();
    }
}
