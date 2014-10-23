<?php
namespace GMO\Beanstalk\Runner;

use Exception;
use GMO\Beanstalk\Exception\JobAwareExceptionInterface;
use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Worker\WorkerInterface;
use GMO\Common\Collections\ArrayCollection;
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
	}

	public function run() {
		if (!$this->queue || !$this->worker) {
			throw new \LogicException('Setup method needs to be called before run');
		}

		$this->log->info("Running worker: " . $this->tubeName);

		$this->attachSignalHandler();

		$this->setupWorker($this->worker);

		$job = new NullJob();
		do {
			$job = $this->getJob($job);
			if ($job instanceof NullJob) {
				continue;
			}
			$this->processJob($job);

		} while ($this->shouldKeepRunning());
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
			$numErrors = $this->getNumberOfErrors($job);
			$this->log->warning($ex->getMessage());

			if ($job->isHandled()) {
				$this->log->warning('Worker should not throw an Exception if job has been handled');
				return;
			}

			if ($ex instanceof JobAwareExceptionInterface &&
				$ex->shouldDelete() &&
				$numErrors >= $ex->deleteAfter()
			) {
				$this->log->warning("Not retrying job...deleting.", array(
					"params"    => $job->getData(),
					"exception" => $ex
				));
				$job->delete();
			} else {
				$this->log->warning("Job failed $numErrors times.", array(
					"params"    => $job->getData(),
					"exception" => $ex
				));
			}
		}
	}

	public function preProcessJob(Job $job) {
		$params = json_decode($job->getData(), true);
		if (!$params) {
			return;
		}
		foreach ($params as $key => $value) {
			if (is_string($value)) {
				$value = trim($value);
			}
			$params[$key] = $value;
		}
		$job->setParsedData(new ArrayCollection($params));
	}

	public function validateJob(Job $job) {
		$params = $job->getData();
		foreach ($this->worker->getRequiredParams() as $reqParam) {
			if (!array_key_exists($reqParam, $params)) {
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

	protected function setupWorker(WorkerInterface $worker) {
		try {
			$worker->setup();
		} catch (Exception $e) {
			$this->log->critical("An error occurred when setting up the worker", array( "exception" => $e ));
			throw $e;
		}
	}

	protected function getJob(Job $previousJob) {
		$this->checkForTerminationSignal();

		# Only log if last call was valid to prevent spamming the log
		if (!$previousJob instanceof NullJob) {
			$this->log->debug("Getting next job...");
		}

		$job = $this->queue->reserve($this->tubeName, static::JOB_RESERVATION_TIMEOUT);

		$this->checkForTerminationSignal();

		$job->bury();

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

	protected function getNumberOfErrors(Job $job) {
		return $job->stats()->reserves();
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
