<?php

namespace GMO\Beanstalk\Test;

use Carbon\Carbon;
use GMO\Beanstalk\Job\JobCollection;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Beanstalk\Tube\Tube;
use GMO\Beanstalk\Tube\TubeControlInterface;

/**
 * ArrayTube is an in-memory representation of a beanstalk tube. Used with ArrayQueue.
 *
 * @see \GMO\Beanstalk\Test\ArrayQueue
 */
class ArrayTube extends Tube
{
    /** @var JobCollection|ArrayJob[] */
    protected $ready;
    /** @var JobCollection|ArrayJob[] */
    protected $reserved;
    /** @var JobCollection|ArrayJob[] */
    protected $delayed;
    /** @var JobCollection|ArrayJob[] */
    protected $buried;

    /** @var Carbon */
    protected $pauseTime;
    protected $pauseDelay = 0;

    protected $cmdPauseCount = 0;
    protected $cmdDeleteCount = 0;
    protected $jobCount = 0;

    public function __construct($name, TubeControlInterface $queue)
    {
        parent::__construct($name, $queue);

        $this->ready = new JobCollection();
        $this->reserved = new JobCollection();
        $this->delayed = new JobCollection();
        $this->buried = new JobCollection();

        $this->pauseTime = new Carbon();
    }

    public function isPaused()
    {
        if ($this->pauseDelay === 0) {
            return false;
        }

        if ($this->pauseTime > new Carbon("-{$this->pauseDelay} sec")) {
            return true;
        } else {
            $this->pauseDelay = 0;

            return false;
        }
    }

    public function pause($delay)
    {
        $this->pauseDelay = $delay;
        $this->pauseTime = new Carbon();
        $this->cmdPauseCount++;
    }

    public function getPauseSeconds()
    {
        if (!$this->isPaused()) {
            return 0;
        }

        return $this->pauseTime->diffInSeconds();
    }

    public function getPauseTimeLeft()
    {
        if (!$this->isPaused()) {
            return 0;
        }

        return $this->pauseTime->diffInSeconds(new Carbon("-{$this->pauseDelay} sec"));
    }

    public function stats()
    {
        $urgentJobs = $this->ready()->filter(function ($i, ArrayJob $job) {
            return $job->getPriority() < QueueInterface::DEFAULT_PRIORITY;
        });

        return new TubeStats(array(
            'name'                  => $this->name,
            'current-jobs-urgent'   => $urgentJobs->count(),
            'current-jobs-ready'    => $this->ready()->count(),
            'current-jobs-reserved' => $this->reserved()->count(),
            'current-jobs-delayed'  => $this->delayed()->count(),
            'current-jobs-buried'   => $this->buried()->count(),
            'total-jobs'            => $this->jobCount,
            'current-using'         => 0,
            'current-waiting'       => 0,
            'current-watching'      => 0,
            'pause'                 => $this->getPauseSeconds(),
            'pause-time-left'       => $this->getPauseTimeLeft(),
            'cmd-delete'            => $this->cmdDeleteCount,
            'cmd-pause-tube'        => $this->cmdPauseCount,
        ));
    }

    public function isEmpty()
    {
        return $this->ready->isEmpty() &&
            $this->reserved->isEmpty() &&
            $this->delayed->isEmpty() &&
            $this->buried->isEmpty();
    }

    public function incrementJobCount($count = 1)
    {
        $this->jobCount += $count;
    }

    public function incrementDeleteCount($count = 1)
    {
        $this->cmdDeleteCount += $count;
    }

    public function ready()
    {
        $this->moveDelayedJobs();
        $this->ready->prioritize();

        return $this->ready;
    }

    public function reserved()
    {
        return $this->reserved;
    }

    public function delayed()
    {
        $this->moveDelayedJobs();

        return $this->delayed;
    }

    public function buried()
    {
        return $this->buried;
    }

    protected function moveDelayedJobs()
    {
        $nowReady = $this->delayed->filter(function ($i, ArrayJob $job) {
            return !$job->isDelayed();
        });
        $this->ready = $this->ready->merge($nowReady);
        foreach ($nowReady as $job) {
            $this->delayed->removeItem($job);
        }
    }
}
