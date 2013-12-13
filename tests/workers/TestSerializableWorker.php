<?php
namespace workers;

use GMO\Beanstalk\SerializableWorker;
use GMO\Common\ISerializable;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

class TestSerializableWorker extends SerializableWorker {

	public static function getNumberOfWorkers() { return 0; }

	protected function getLogger() {
		return new NullLogger();
	}

	protected function processSerializableObject(ISerializable $obj) {
		$this->processResult = $obj->toArray();
	}

	public function getJobErrors() {
		return $this->jobErrors;
	}

	public $processResult = null;
}

UnitTestWorkerManager::runWorker();