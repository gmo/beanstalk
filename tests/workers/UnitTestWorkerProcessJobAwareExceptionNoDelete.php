<?php
namespace workers;

use GMO\Beanstalk\AbstractWorker;
use GMO\Beanstalk\IJobAwareException;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

class UnitTestWorkerProcessJobAwareExceptionNoDelete extends AbstractWorker {
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
		throw new NonDeleteJobAwareException("The process fails");
	}

	public $processResult = null;
}

class NonDeleteJobAwareException extends \Exception implements IJobAwareException {

	public function shouldDelete() { return false; }

	public function deleteAfter() { return 1; }
}

UnitTestWorkerManager::runWorker();