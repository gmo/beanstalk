<?php
namespace GMO\Beanstalk\Worker;

use GMO\Beanstalk\Queue\TubeControlInterface;
use GMO\Beanstalk\Runner\BaseRunner;
use GMO\Common\String;

/**
 * Sets default values for WorkerInterface
 */
abstract class AbstractWorker implements WorkerInterface {

	public static function getTubeName() {
		return String::className(static::className());
	}

	public static function getRunnerClass() { return BaseRunner::className(); }

	public static function getNumberOfWorkers() { return 1; }

	public static function getTimeToRun() { return TubeControlInterface::DEFAULT_TTR; }

	public function getRequiredParams() { return array(); }

	public function setup() { }

	public static function className() { return get_called_class(); }
}
