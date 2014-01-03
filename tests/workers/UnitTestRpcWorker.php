<?php
namespace workers;

use GMO\Beanstalk\AbstractRpcWorker;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

require_once __DIR__ . "/../tester_autoload.php";

class UnitTestRpcWorker extends AbstractRpcWorker {
	protected function getLogger() {
		return new NullLogger();
	}

	protected function setup() {
		$this->setToRunOnce();
	}
	

	protected function process( $params ) {
		return intval($params['a']) * intval($params['b']);
	}
}

UnitTestWorkerManager::runWorker();