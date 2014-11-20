<?php
namespace workers;

use GMO\Beanstalk\Job\Job;

class UnitTestWorkerProcessGenericException extends AbstractTestWorker {

	public static function getRequiredParams() { return array( "param1", "param2" ); }

	public function process(Job $job) {
		throw new \Exception("The process fails");
	}
}
