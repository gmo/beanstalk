<?php
namespace workers;

class NullWorker extends AbstractTestWorker {

	public static function getNumberOfWorkers() { return 3; }

	public function process($job) { }
}
