<?php
namespace GMO\Beanstalk\Worker;

use Runner\RpcRunner;

abstract class RpcWorker extends AbstractWorker {

	/** @inheritdoc */
	public static function getRunnerClass() { return RpcRunner::className(); }
}
