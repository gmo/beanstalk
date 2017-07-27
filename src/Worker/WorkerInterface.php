<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Worker;

use Bolt\Collection\Bag;
use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\JobError\JobErrorHandlerInterface;
use Gmo\Beanstalk\Runner\RunnerInterface;

interface WorkerInterface
{
    /**
     * The tube name the worker should pull jobs from.
     *
     * @return string
     */
    public static function getTubeName();

    /**
     * Returns the runner.
     *
     * @return RunnerInterface
     */
    public static function getRunner();

    /**
     * Return number of workers to spawn.
     *
     * @return int
     */
    public static function getNumberOfWorkers();

    /**
     * Return the number of seconds a job for
     * this worker should be allowed to run.
     *
     * @return int seconds
     */
    public static function getTimeToRun();

    /**
     * Return a list of job error handlers.
     *
     * @return JobErrorHandlerInterface[]|Bag
     */
    public static function getErrorHandlers();

    /**
     * Return an array of parameters required for job to continue.
     *
     * @return string[]|Bag
     */
    public static function getRequiredParams();

    /**
     * Returns a logger instance for worker.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public static function getLogger();

    /**
     * Setup worker to run. Called only one time.
     */
    public function setup();

    /**
     * Process each job.
     *
     * @param Job $job
     */
    public function process(Job $job);

    /**
     * Called when the worker is stopped.
     */
    public function onStop();
}
