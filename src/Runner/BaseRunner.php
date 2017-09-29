<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Runner;

use Bolt\Collection\Bag;
use Exception;
use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\JobError\Action\JobActionInterface;
use Gmo\Beanstalk\Job\JobError\HasJobErrorInterface;
use Gmo\Beanstalk\Job\JobError\JobError;
use Gmo\Beanstalk\Job\JobError\JobErrorHandlerInterface;
use Gmo\Beanstalk\Job\JobError\JobErrorInterface;
use Gmo\Beanstalk\Job\NullJob;
use Gmo\Beanstalk\Job\UnserializableJob;
use Gmo\Beanstalk\Queue\QueueInterface;
use Gmo\Beanstalk\Worker\ContainerAwareWorker;
use Gmo\Beanstalk\Worker\WorkerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Abstracts the repetitive worker tasks, such as getting jobs and validating parameters.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 */
class BaseRunner implements RunnerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const JOB_RESERVATION_TIMEOUT = 5;

    /** @var WorkerInterface */
    protected $worker;
    /** @var bool Boolean for running loop */
    protected $keepRunning = true;
    /** @var QueueInterface */
    protected $queue;
    /** @var string Tube name cached for performance */
    protected $tubeName;
    /** @var JobErrorHandlerInterface[]|Bag Error handlers cached for performance */
    protected $errorHandlers;

    public function setup(QueueInterface $queue, WorkerInterface $worker)
    {
        $this->queue = $queue;
        $this->worker = $worker;
        $this->tubeName = $worker::getTubeName();
        $this->errorHandlers = $worker::getErrorHandlers();

        $logger = $worker::getLogger();
        $this->setLogger($logger);
        $queue->setLogger($logger);

        $this->attachLoggerToErrorHandlers();

        $this->attachSignalHandler();
    }

    public function run()
    {
        if (!$this->queue || !$this->worker) {
            throw new \LogicException('Setup method needs to be called before run');
        }

        $this->setupWorker($this->worker);

        $this->logger->info('Running worker');

        $job = new NullJob();
        do {
            $job = $this->getJob($job);
            if (!$job instanceof NullJob) {
                $this->processJob($job);
            }
        } while ($this->shouldKeepRunning());
        $this->logger->debug('Stopping worker...');
        $this->worker->onStop();
        $this->logger->info('Worker stopped');
    }

    public function processJob(Job $job)
    {
        $job = $this->preProcessJob($job);
        if ($job->isHandled()) {
            return;
        }

        if (!$this->validateJob($job)) {
            $job->delete();

            return;
        }

        try {
            $this->logger->debug('Processing job');
            $this->worker->process($job);
            $this->postProcessJob($job);
        } catch (Exception $ex) {
            try {
                $this->handleError($job, $ex);
            } catch (Exception $e) {
                $this->logger->warning('Queue command failed', [
                    'exception' => $e,
                ]);
            }
        }
    }

    public function preProcessJob(Job $job)
    {
        if ($job instanceof UnserializableJob) {
            $this->logger->error('Burying unserializable job');
            $job->bury();
        }

        return $job;
    }

    public function validateJob(Job $job)
    {
        $params = $job->getData();
        if (is_scalar($params)) {
            return true;
        }
        foreach ($this->worker::getRequiredParams() as $reqParam) {
            if (!isset($params[$reqParam])) {
                $this->logger->error('Job is missing required parameter', [
                    'missing' => $reqParam,
                ]);

                return false;
            }
        }

        return true;
    }

    public function postProcessJob(Job $job)
    {
        if ($job->isHandled()) {
            return;
        }
        $this->logger->debug('Deleting finished job');
        $job->delete();
    }

    public function setupWorker(WorkerInterface $worker)
    {
        if ($worker instanceof ContainerAwareWorker) {
            $container = $worker->getContainer();
            $this->queue = $container->get('beanstalk.queue');

            if ($container->has('beanstalk.logger.processor.worker')) {
                $container->get('beanstalk.logger.processor.worker')->setName($this->tubeName);
            }

            if ($this->logger instanceof LoggerAwareInterface && $container->has('logger.new')) {
                $this->logger->setLogger($container->get('logger.new')('Worker'));
            }
        }

        try {
            $worker->setup();
        } catch (Exception $e) {
            $this->logger->critical('An error occurred when setting up the worker', ['exception' => $e]);
            throw $e;
        }
    }

    public function getJob(Job $previousJob)
    {
        $this->checkForTerminationSignal();

        $job = $this->queue->reserve($this->tubeName, static::JOB_RESERVATION_TIMEOUT);

        $this->checkForTerminationSignal();

        return $job;
    }

    public function shouldKeepRunning()
    {
        return $this->keepRunning;
    }

    public function stopRunning()
    {
        $this->keepRunning = false;
    }

    /**
     * Handled Exception thrown by worker.
     *
     * @param Job       $job
     * @param Exception $ex
     */
    protected function handleError(Job $job, Exception $ex)
    {
        $numRetries = $this->getNumberOfRetries($job);
        $this->logger->warning($ex->getMessage(), [
            'exception' => $ex,
        ]);

        if ($job->isHandled()) {
            $this->logger->warning('Worker should not throw an Exception if job has been handled');

            return;
        }

        $jobError = $this->determineJobError($ex);

        if ($jobError->shouldPauseTube()) {
            $this->pauseTube($jobError->getDelay($numRetries));
        }
        if ($numRetries < $jobError->getMaxRetries()) {
            $this->delayJob($job, $jobError, $ex, $numRetries);
        } elseif ($jobError->getActionToTake() === JobActionInterface::DELETE) {
            $this->deleteJob($job, $ex, $numRetries);
        } else {
            $this->buryJob($job, $ex, $numRetries);
        }
    }

    protected function pauseTube($delay)
    {
        $this->logger->notice('Pausing tube', [
            'delay' => $delay,
        ]);
        $this->queue->pause($this->tubeName, $delay);
    }

    protected function buryJob(Job $job, $exception, $numErrors)
    {
        $this->logger->warning('Burying failed job', [
            'numErrors' => $numErrors,
            'exception' => $exception,
        ]);
        $job->bury();
    }

    protected function deleteJob(Job $job, $exception, $numErrors)
    {
        $this->logger->notice('Deleting failed job', [
            'numErrors' => $numErrors,
            'exception' => $exception,
        ]);
        $job->delete();
    }

    protected function delayJob(Job $job, JobErrorInterface $jobError, $exception, $numErrors)
    {
        $delay = !$jobError->shouldPauseTube() ? $jobError->getDelay($numErrors) : 0;
        $this->logger->notice('Delaying failed job', [
            'numErrors' => $numErrors,
            'delay'     => $delay,
            'exception' => $exception,
        ]);
        $job->release($delay);
    }

    protected function getNumberOfRetries(Job $job)
    {
        return $job->stats()->releases();
    }

    protected function attachSignalHandler()
    {
        pcntl_signal(SIGTERM, [$this, 'stopRunning']);
    }

    protected function checkForTerminationSignal()
    {
        pcntl_signal_dispatch();
    }

    /**
     * Determine job error from error handlers or exception.
     *
     * @param Exception $ex
     *
     * @return JobErrorInterface
     */
    protected function determineJobError(Exception $ex)
    {
        foreach ($this->errorHandlers as $handler) {
            if ($jobError = $handler->handle($ex)) {
                return $jobError;
            }
        }

        if ($ex instanceof HasJobErrorInterface) {
            return $ex->getJobError();
        }

        if ($ex instanceof JobErrorInterface) {
            return $ex;
        }

        return new JobError();
    }

    protected function attachLoggerToErrorHandlers()
    {
        foreach ($this->errorHandlers as $handler) {
            if ($handler instanceof LoggerAwareInterface) {
                $handler->setLogger($this->logger);
            }
        }
    }
}
