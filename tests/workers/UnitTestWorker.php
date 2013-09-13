<?php
namespace workers;

use GMO\Beanstalk\AbstractWorker;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

class UnitTestWorker extends AbstractWorker {
	protected function getLogger() {
		return new NullLogger();
	}

	protected function getRequiredParams() { return array( "param1", "param2" ); }

	protected function process( $params ) {
		$this->processResult = json_encode( $params );
	}

	public $processResult = null;
}

UnitTestWorkerManager::runWorker();