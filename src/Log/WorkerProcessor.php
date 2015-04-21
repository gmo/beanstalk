<?php
namespace GMO\Beanstalk\Log;

/**
 * Adds worker name to Monolog Logger
 */
class WorkerProcessor {

	public function __invoke(array $record) {
		$record['extra']['worker'] = $this->workerName;
		return $record;
	}

	public function __construct($workerName) {
		$this->workerName;
	}

	protected $workerName;
}
