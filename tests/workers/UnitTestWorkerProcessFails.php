<?php
namespace workers;

use GMO\Beanstalk\AbstractWorker;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

class UnitTestWorkerProcessFails extends AbstractWorker {
	public static function getNumberOfWorkers() { return 0; }

	protected function getLogger() {
		return new NullLogger();
	}

	public function getNumberOfErrorsForCurrentJob() {
		$id = $this->currentJob->getId();
		return $this->jobErrors[$id];
	}

	protected function getRequiredParams() { return array( "param1", "param2" ); }

	protected function process( $params ) {
		throw new \Exception("The process fails");
	}

	public $processResult = null;
}

UnitTestWorkerManager::runWorker();