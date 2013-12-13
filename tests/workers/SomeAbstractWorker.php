<?php
namespace workers;

use GMO\Beanstalk\AbstractWorker;
use Psr\Log\NullLogger;

abstract class SomeAbstractWorker extends AbstractWorker {

	public static function getNumberOfWorkers() { return 3; }

	protected function getLogger() {
		return new NullLogger();
	}

	protected function process( $params ) { }
}
