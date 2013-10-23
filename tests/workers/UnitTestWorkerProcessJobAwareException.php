<?php
namespace workers;

use GMO\Beanstalk\AbstractWorker;
use GMO\Beanstalk\IJobAwareException;
use Psr\Log\NullLogger;
use UnitTestWorkerManager;

class UnitTestWorkerProcessJobAwareException extends AbstractWorker {
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
		throw new JobAwareException("The process fails");
	}

	public $processResult = null;
}

class JobAwareException extends \Exception implements IJobAwareException {

	public function shouldDelete() { return true; }

	public function deleteAfter() { return 2; }
}

UnitTestWorkerManager::runWorker();