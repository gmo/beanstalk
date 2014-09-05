<?php
namespace GMO\Beanstalk\Worker;

use Runner\RpcRunner;

abstract class RpcWorker extends AbstractWorker {

	/** @inheritdoc */
	public function getRunnerClass() { return RpcRunner::CLS; }
}
