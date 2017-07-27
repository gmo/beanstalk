<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Test;

use Bolt\Collection\ImmutableBag;
use Bolt\Common\Serialization;
use Gmo\Beanstalk\Exception\RangeException;
use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\JobCollection;
use Gmo\Beanstalk\Job\NullJob;
use Gmo\Beanstalk\Log\JobProcessor;
use Gmo\Beanstalk\Queue\QueueInterface;
use Gmo\Beanstalk\Queue\Response\ServerStats;
use Gmo\Beanstalk\Tube\TubeCollection;
use Psr\Log\LoggerAwareTrait;

/**
 * ArrayQueue is an in-memory implementation of the QueueInterface.
 *
 * Most useful for testing.
 *
 * Note: Somethings are not implemented; notably job TTR and server stats.
 */
class ArrayQueue implements QueueInterface
{
    use LoggerAwareTrait;

    /** @var JobCollection|ArrayJob[] */
    protected $jobs;
    /** @var TubeCollection|ArrayTube[] */
    protected $tubes;
    protected $jobCounter = 0;
    /** @var JobProcessor */
    protected $logProcessor;

    public function __construct()
    {
        $this->jobs = new JobCollection();
        $this->tubes = new TubeCollection();
        $this->logProcessor = new JobProcessor();
    }

    public function release(Job $job, $priority = null, $delay = null)
    {
        /** @var ArrayJob $job */
        if ($this->isNullJob($job)) {
            return;
        }

        $job->incrementReleases();

        $tube = $this->tube($job->stats()->tube());
        $tube->reserved()->removeItem($job);

        $job->setDelay($delay);
        $job->setPriority($priority);
        if ($delay > 0) {
            $job->setState('delayed');
            $tube->delayed()->add($job);
        } else {
            $job->setState('ready');
            $tube->ready()->add($job);
        }
    }

    public function bury(Job $job, $priority = null)
    {
        /** @var ArrayJob $job */
        if ($this->isNullJob($job)) {
            return;
        }

        $job->setPriority($priority);

        $tube = $this->tube($job->stats()->tube());
        $tube->reserved()->removeItem($job);

        $job->setState('buried');
        $job->incrementBuries();

        $tube->buried()->add($job);
    }

    public function delete($job)
    {
        if ($this->isNullJob($job)) {
            return;
        }
        $stats = $job->stats();

        $tube = $this->tube($stats->tube());
        $tube->{$stats->state()}()->removeItem($job);

        $this->jobs->remove($job->getId());
        $tube->incrementDeleteCount();
        $this->removeEmptyTube($tube);
    }

    public function kickJob($job)
    {
        /** @var ArrayJob $job */
        if ($this->isNullJob($job)) {
            return;
        }

        $tube = $this->tube($job->stats()->tube());
        if ($job instanceof ArrayJob && $job->isDelayed()) {
            $tube->delayed()->removeItem($job);
        } else {
            $tube->buried()->removeItem($job);
        }

        $job->setState('ready');
        $job->incrementKicks();

        $tube->ready()->add($job);
        $job->setDelay(0);
    }

    /** @inheritdoc */
    public function statsJob($job)
    {
        if (is_numeric($job)) {
            $job = $this->jobs[(int) $job];
        }

        return $job->stats();
    }

    public function touch(Job $job)
    {
    }

    public function statsAllTubes()
    {
        $stats = [];
        foreach ($this->tubes as $tube) {
            $stats[$tube->name()] = $this->statsTube($tube);
        }

        return new ImmutableBag($stats);
    }

    public function statsServer()
    {
        return new ServerStats();
    }

    public function push($tube, $data, $priority = null, $delay = null, $ttr = null)
    {
        $priority = $priority ?: static::DEFAULT_PRIORITY;
        if ($priority < 0 || $priority > 4294967295) {
            throw new RangeException("Priority must be between 0 and 4294967295. Given: $priority");
        }
        $delay = $delay ?: static::DEFAULT_DELAY;

        $data = Serialization::dump($data);
        $job = new ArrayJob($this->jobCounter++, $data, $priority, $delay, $tube, $this);
        $job->setState($delay > 0 ? 'delayed' : 'ready');

        $this->jobs[$job->getId()] = $job;

        $tube = $this->tube($tube);
        if ($delay > 0) {
            $tube->delayed()->add($job);
        } else {
            $tube->ready()->add($job);
        }
        $tube->incrementJobCount();

        return $job->getId();
    }

    public function reserve($tube, $timeout = null, $stopWatching = false)
    {
        $tube = $this->tube($tube);

        if ($tube->isPaused()) {
            $this->logProcessor->setCurrentJob(null);

            return new NullJob();
        }

        /** @var ArrayJob|NullJob $job */
        $job = $tube->ready()->removeFirst();
        $this->logProcessor->setCurrentJob($job);
        if ($this->isNullJob($job)) {
            return $job;
        }

        $job->setState('reserved');
        $job->incrementReserves();

        $tube->reserved()->add($job);

        $job->setData(Serialization::parse($job->getData()));
        $job->resetHandled();

        return $job;
    }

    public function kickTube($tube, $num = -1)
    {
        $tube = $this->tube($tube);

        $kicked = 0;
        if (($buriedCount = $tube->buried()->count()) > 0) {
            $numToKick = $num > 0 ? min($num, $buriedCount) : $buriedCount;
            $kicked += $numToKick;
            $num -= $numToKick;

            /** @var ArrayJob[] $jobsToKick */
            $jobsToKick = $tube->buried()->slice(0, $numToKick);
            foreach ($jobsToKick as $job) {
                $this->kickJob($job);
            }

            if ($num === 0) {
                return $kicked;
            }
        }
        $numToKick = $tube->delayed()->count();
        if ($num > 0) {
            $numToKick = min($numToKick, $num);
        }
        $kicked += $numToKick;

        /** @var ArrayJob[] $jobsToKick */
        $jobsToKick = $tube->delayed()->slice(0, $numToKick);
        foreach ($jobsToKick as $job) {
            $this->kickJob($job);
        }

        return $kicked;
    }

    public function peekJob($jobId)
    {
        return $this->jobs[$jobId];
    }

    public function peekReady($tube)
    {
        return $this->tube($tube)->ready()->first();
    }

    public function peekBuried($tube)
    {
        return $this->tube($tube)->buried()->first();
    }

    public function peekDelayed($tube)
    {
        return $this->tube($tube)->delayed()->first();
    }

    public function deleteReadyJobs($tube, $num = -1)
    {
        $tube = $this->tube($tube);

        $readyCount = $tube->ready()->count();
        $numToDelete = $num > 0 ? min($num, $readyCount) : $readyCount;
        /** @var ArrayJob[] $jobsToDelete */
        $jobsToDelete = $tube->ready()->slice(0, $numToDelete);
        foreach ($jobsToDelete as $job) {
            $this->delete($job);
        }

        $this->removeEmptyTube($tube);
    }

    public function deleteBuriedJobs($tube, $num = -1)
    {
        $tube = $this->tube($tube);

        $buriedCount = $tube->buried()->count();
        $numToDelete = $num > 0 ? min($num, $buriedCount) : $buriedCount;
        /** @var ArrayJob[] $jobsToDelete */
        $jobsToDelete = $tube->buried()->slice(0, $numToDelete);
        foreach ($jobsToDelete as $job) {
            $this->delete($job);
        }

        $this->removeEmptyTube($tube);
    }

    public function deleteDelayedJobs($tube, $num = -1)
    {
        $tube = $this->tube($tube);

        $delayedCount = $tube->delayed()->count();
        $numToDelete = $num > 0 ? min($num, $delayedCount) : $delayedCount;
        /** @var ArrayJob[] $jobsToDelete */
        $jobsToDelete = $tube->delayed()->slice(0, $numToDelete);
        foreach ($jobsToDelete as $job) {
            $this->delete($job);
        }

        $this->removeEmptyTube($tube);
    }

    public function pause($tube, $delay)
    {
        $tube = $this->tube($tube);
        $tube->pause($delay);
    }

    public function statsTube($tube)
    {
        $tube = $this->tube($tube);
        $this->removeEmptyTube($tube);

        return $tube->stats();
    }

    /**
     * @param ArrayTube|string $tube
     *
     * @return ArrayTube
     */
    public function tube($tube)
    {
        if ($tube instanceof ArrayTube) {
            return $tube;
        }

        if (!$this->tubes->has($tube)) {
            $this->tubes[$tube] = new ArrayTube($tube, $this);
        }

        return $this->tubes->get($tube);
    }

    public function tubes()
    {
        return $this->tubes;
    }

    public function getJobProcessor()
    {
        return $this->logProcessor;
    }

    protected function removeEmptyTube(ArrayTube $tube)
    {
        if (!$tube->isPaused() && $tube->isEmpty()) {
            $this->tubes->removeItem($tube);
        }
    }

    protected function isNullJob(Job $job)
    {
        return $job->getId() === -1;
    }
}
