<?php
namespace GMO\Beanstalk\Runner;

use Exception;
use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\JobError\Action\JobActionInterface;
use GMO\Beanstalk\Job\JobError\JobErrorInterface;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Worker\WorkerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstracts the repetitive worker tasks, such as getting jobs and validating parameters.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 */
class BaseRunner implements RunnerInterface, LoggerAwareInterface {

	const JOB_RESERVATION_TIMEOUT = 5;

	public function setup(QueueInterface $queue, WorkerInterface $worker) {
		$this->queue = $queue;
		$this->worker = $worker;
		$this->tubeName = $worker->getTubeName();

		$log = $worker->getLogger();
		$this->setLogger($log);
		$queue->setLogger($log);

		$this->attachSignalHandler();
	}

	public function run() {
		if (!$this->queue || !$this->worker) {
			throw new \LogicException('Setup method needs to be called before run');
		}

		$this->log->info("Running worker: " . $this->tubeName);

		$this->setupWorker($this->worker);

		$job = new NullJob();
		do {
			$job = $this->getJob($job);
			if (!$job instanceof NullJob) {
				$this->processJob($job);
			}
		} while ($this->shouldKeepRunning());
		$this->log->info('Stopping worker...');
		$this->worker->onStop();
		$this->log->info('Worker stopped');
	}

	public function processJob(Job $job) {
		$this->preProcessJob($job);

		if (!$this->validateJob($job)) {
			$this->log->error('Job missing required params is being deleted!');
			$job->delete();
			return;
		}

		try {
			$this->log->debug('Processing job');
			$this->worker->process($job);
			$this->postProcessJob($job);
		} catch (Exception $ex) {
			try {
				$this->handleError($job, $ex);
			} catch (Exception $e) {
				$this->log->warning('Queue command failed', array(
					'exception' => $e,
				));
			}
		}
	}

	public function preProcessJob(Job $job) { }

	public function validateJob(Job $job) {
		$params = $job->getData();
		if (is_scalar($params)) {
			return true;
		}
		foreach ($this->worker->getRequiredParams() as $reqParam) {
			if (!$params->containsKey($reqParam)) {
				$this->log->error("Job is missing required param: \"$reqParam\"", array( "params" => $params ));
				return false;
			}
		}
		return true;
	}

	public function postProcessJob(Job $job) {
		if ($job->isHandled()) {
			return;
		}
		$this->log->debug("Deleting the current job from: " . $this->tubeName);
		$job->delete();
	}

	public function setupWorker(WorkerInterface $worker) {
		try {
			$worker->setup();
		} catch (Exception $e) {
			$this->log->critical("An error occurred when setting up the worker", array( "exception" => $e ));
			throw $e;
		}
	}

	public function getJob(Job $previousJob) {
		$this->checkForTerminationSignal();

		# Only log if last call was valid to prevent spamming the log
		if (!$previousJob instanceof NullJob) {
			$this->log->debug("Getting next job...");
		}

		$job = $this->queue->reserve($this->tubeName, static::JOB_RESERVATION_TIMEOUT);

		$this->checkForTerminationSignal();

		return $job;
	}

	public function shouldKeepRunning() {
		return $this->keepRunning;
	}

	public function stopRunning() {
		$this->keepRunning = false;
	}

	/** @inheritdoc */
	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
	}

	public static function className() { return get_called_class(); }

	/**
	 * Handled Exception thrown by worker
	 * @param Job       $job
	 * @param Exception $ex
	 */
	protected function handleError(Job $job, Exception $ex) {
		$numRetries = $this->getNumberOfRetries($job);
		$this->log->warning($ex->getMessage());

		if ($job->isHandled()) {
			$this->log->warning('Worker should not throw an Exception if job has been handled');
			return;
		}

		if (!$ex instanceof JobErrorInterface) {
			$this->buryJob($job, $ex, $numRetries);
		}

		if ($ex->shouldPauseTube()) {
			$this->pauseTube($ex->getDelay($numRetries));
		}
		if ($numRetries < $ex->getMaxRetries()) {
			$this->delayJob($job, $ex, $numRetries);
		} elseif ($ex->getActionToTake() === JobActionInterface::DELETE) {
			$this->deleteJob($job, $ex, $numRetries);
		} else {
			$this->buryJob($job, $ex, $numRetries);
		}
	}

	protected function pauseTube($delay) {
		$this->log->notice('Pausing tube', array(
			'tube' => $this->tubeName,
			'delay' => $delay,
		));
		$this->queue->pause($this->tubeName, $delay);
	}

	protected function buryJob(Job $job, $exception, $numErrors) {
		$this->log->warning("Burying failed job", array(
			"numErrors" => $numErrors,
			"params"    => $job->getData(),
			"exception" => $exception
		));
		$job->bury();
	}

	protected function deleteJob(Job $job, $exception, $numErrors) {
		$this->log->notice("Deleting failed job", array(
			"numErrors" => $numErrors,
			"params"    => $job->getData(),
			"exception" => $exception
		));
		$job->delete();
	}

	protected function delayJob(Job $job, JobErrorInterface $exception, $numErrors) {
		$delay = !$exception->shouldPauseTube() ? $exception->getDelay($numErrors) : 0;
		$this->log->notice("Delaying failed job", array(
			"numErrors" => $numErrors,
			"delay" 	=> $delay,
			"params"    => $job->getData(),
			"exception" => $exception
		));
		$job->release($delay);
	}

	protected function getNumberOfRetries(Job $job) {
		return $job->stats()->releases();
	}

	protected function attachSignalHandler() {
		pcntl_signal(SIGTERM, array( $this, 'stopRunning' ));
	}

	protected function checkForTerminationSignal() {
		pcntl_signal_dispatch();
	}

	/** @var WorkerInterface */
	protected $worker;

	/** @var bool Boolean for running loop */
	protected $keepRunning = true;

	/** @var string */
	protected $tubeName;

	/** @var QueueInterface */
	protected $queue;

	/** @var \Psr\Log\LoggerInterface Worker logger */
	protected $log;

}
