<?php
namespace workers;

class UnitTestWorker extends AbstractTestWorker {

	public static function getRequiredParams() { return array( "param1", "param2" ); }

	public function process($job) {
		$job->setResult($job->getData()->getValues());
	}
}
