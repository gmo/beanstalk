<?php
namespace workers;

use GMO\Beanstalk\AbstractWorker;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

class UnitTestWorkerSetupFailure extends AbstractWorker {
	public static function getTubeName() { return "UnitTestWorker"; }

	public static function getNumberOfWorkers() { return 0; }

	protected function getLogger() {
		return new NullLogger();
	}

	public function setup() {
		throw new \Exception("Setup function failed!");
	}

	protected function getRequiredParams() { return array( "param1", "param2" ); }

	protected function process( $params ) {
		$this->processResult = json_encode( $params );
	}

	public $processResult = null;
}

UnitTestWorkerManager::runWorker();