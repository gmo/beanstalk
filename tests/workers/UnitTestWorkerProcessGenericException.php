<?php
namespace workers;

class UnitTestWorkerProcessGenericException extends AbstractTestWorker {

	public static function getRequiredParams() { return array( "param1", "param2" ); }

	public function process($job) {
		throw new \Exception("The process fails");
	}
}
