<?php
namespace GMO\Beanstalk\Worker;

use GMO\Beanstalk\Runner\BaseRunner;
use GMO\Common\String;

abstract class AbstractWorker implements WorkerInterface {

	/**
	 * Return worker name. By default it is the class name.
	 * @return string
	 */
	public static function getTubeName() {
		return String::className(static::className());
	}

	/** @inheritdoc */
	public static function getRunnerClass() { return BaseRunner::className(); }

	/**
	 * Return number of workers to spawn. By default it is one.
	 * @return int
	 */
	public static function getNumberOfWorkers() { return 1; }

	/**
	 * Return an array of parameters required for job to continue.
	 * By default it is empty.
	 * @return array
	 */
	public function getRequiredParams() { return array(); }

	public function setup() { }

	public static function className() { return get_called_class(); }
}
