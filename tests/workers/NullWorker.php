<?php
namespace workers;

use GMO\Beanstalk\Job\Job;

class NullWorker extends AbstractTestWorker {

	public static function getNumberOfWorkers() { return 3; }

	public function process(Job $job) { }
}
