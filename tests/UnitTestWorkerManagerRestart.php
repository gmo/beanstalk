<?php

use GMO\Beanstalk\WorkerManager;

class UnitTestWorkerManagerRestart extends WorkerManager {

	public $stopCalled = false;
	public $startCalled = false;

	public function startWorkers() {
		$this->startCalled = true;
	}

	public function stopWorkers() {
		$this->stopCalled = true;
	}
}