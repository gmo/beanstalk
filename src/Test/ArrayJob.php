<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Test;

use Carbon\Carbon;
use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\JobControlInterface;
use Gmo\Beanstalk\Queue\Response\JobStats;

/**
 * ArrayJob is used by {@see ArrayQueue} to
 * determine if the job is still delayed and the priority.
 *
 * @see ArrayQueue
 */
class ArrayJob extends Job
{
    /** @var JobStats */
    protected $stats;
    /** @var Carbon */
    protected $created;
    /** @var Carbon */
    protected $delayTime;
    protected $delay = 0;
    protected $priority = 0;

    /**
     * @param int                 $id
     * @param string              $data
     * @param int                 $priority
     * @param int                 $delay
     * @param string              $tubeName
     * @param JobControlInterface $queue
     */
    public function __construct($id, $data, $priority, $delay, $tubeName, JobControlInterface $queue)
    {
        parent::__construct($id, $data, $queue);
        $this->stats = new JobStats([
            'id'   => $id,
            'tube' => $tubeName,
        ]);
        $this->setPriority($priority);
        $this->setDelay($delay);
        $this->created = new Carbon();
    }

    public function stats()
    {
        $this->stats = $this->stats->replace([
            'age' => $this->created->diffInSeconds(),
        ]);

        return $this->stats;
    }

    public function isDelayed()
    {
        if ($this->delay === 0) {
            return false;
        }

        if ($this->delayTime > new Carbon("-{$this->delay} sec")) {
            return true;
        }
        $this->delay = 0;

        return false;
    }

    public function setDelay($delay)
    {
        $this->delay = (int) $delay;
        $this->delayTime = new Carbon();
    }

    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;

        $this->stats = $this->stats->replace([
            'pri' => $this->priority,
        ]);
    }

    public function resetHandled()
    {
        $this->handled = false;
    }

    public function setState($state)
    {
        $this->stats = $this->stats->replace([
            'state' => $state,
        ]);
    }

    public function incrementReserves()
    {
        $this->incrementStat('reserves');
    }

    public function incrementReleases()
    {
        $this->incrementStat('releases');
    }

    public function incrementBuries()
    {
        $this->incrementStat('buries');
    }

    public function incrementKicks()
    {
        $this->incrementStat('kicks');
    }

    protected function incrementStat($name)
    {
        $this->stats = $this->stats->replace([
            $name => $this->stats[$name] + 1,
        ]);
    }
}
