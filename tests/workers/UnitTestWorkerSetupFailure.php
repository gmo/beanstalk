<?php
namespace workers;

class UnitTestWorkerSetupFailure extends AbstractTestWorker {

	public static function tubeName() { return "UnitTestWorker"; }

	public function setup() {
		throw new \Exception("Setup function failed!");
	}

	public function process($job) { }
}
