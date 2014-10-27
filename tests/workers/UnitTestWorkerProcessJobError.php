<?php
namespace workers;

use GMO\Beanstalk\Exception\Job\DeleteJobImmediatelyException;

class UnitTestWorkerProcessJobError extends AbstractTestWorker {

	public function getRequiredParams() { return array( "param1", "param2" ); }

	public function process($job) {
		throw new DeleteJobImmediatelyException(new \Exception("The process fails"));
	}
}
