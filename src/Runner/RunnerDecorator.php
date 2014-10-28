<?php
namespace GMO\Beanstalk\Runner;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Worker\WorkerInterface;

/**
 * This class is the base for decorating runners
 */
abstract class RunnerDecorator extends BaseRunner {

	public function setupWorker(WorkerInterface $worker) {
		$this->runner->setupWorker($worker);
	}

	public function getJob(Job $previousJob) {
		return $this->runner->getJob($previousJob);
	}

	public function setup(QueueInterface $queue, WorkerInterface $worker) {
		parent::setup($queue, $worker);
		$this->runner->setup($queue, $worker);
	}

	public function preProcessJob(Job $job) {
		$this->runner->preProcessJob($job);
	}

	public function validateJob(Job $job) {
		return $this->runner->validateJob($job);
	}

	public function processJob(Job $job) {
		$this->runner->processJob($job);
	}

	public function postProcessJob(Job $job) {
		$this->runner->postProcessJob($job);
	}

	public function shouldKeepRunning() {
		return $this->runner->shouldKeepRunning();
	}

	public function stopRunning() {
		$this->runner->stopRunning();
	}

	protected function attachSignalHandler() { }

	protected function checkForTerminationSignal() {
		throw new \LogicException();
	}

	public function __construct(RunnerInterface $runner) {
		$this->runner = $runner;
	}

	/** @var RunnerInterface */
	protected $runner;
}
