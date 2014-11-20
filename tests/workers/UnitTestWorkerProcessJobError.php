<?php
namespace workers;

use GMO\Beanstalk\Exception\Job\DeleteJobImmediatelyException;
use GMO\Beanstalk\Job\Job;

class UnitTestWorkerProcessJobError extends AbstractTestWorker {

	public static function getRequiredParams() { return array( "param1", "param2" ); }

	public function process(Job $job) {
		throw new DeleteJobImmediatelyException(new \Exception("The process fails"));
	}
}
