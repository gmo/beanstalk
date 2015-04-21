<?php
namespace GMO\Beanstalk\Log;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue\QueueInterface;

class JobProcessor {

	public function setCurrentJob(Job $job = null) {
		$this->currentJob = $job;
	}

	public function __invoke(array $record) {
		if (!$this->currentJob) {
			return $record;
		}

		$params = array(
			'id' => $this->currentJob->getId(),
			'data' => $this->currentJob->getData(),
			'result' => $this->currentJob->getResult(),
		);
		$record['extra']['job'] = $params;
		return $record;
	}

	public function __construct(QueueInterface $queue) {
		$this->queue = $queue;
	}

	protected $queue;
	/** @var Job|null */
	protected $currentJob;
}
