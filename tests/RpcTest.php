<?php

use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Beanstalk\Queue\Queue;
use GMO\Beanstalk\Test\QueueTestCase;
use workers\UnitTestRpcWorker;

require_once __DIR__ . "/tester_autoload.php";

class RpcTest extends QueueTestCase {

	public function test_rpc() {
		$data = array('a' => 2, 'b' => 2);
		$manager = new WorkerManager(WORKER_DIR);
		$manager->startWorkers('UnitTestRpcWorker');
		$result = UnitTestRpcWorker::runRpc(new Queue(), $data);
		$this->assertSame(4, $result);
	}

	/**
	 * @expectedException \GMO\Beanstalk\Exception\RpcTimeoutException
	 */
	public function test_rpc_call_timeout() {
		UnitTestRpcWorker::runRpc(static::$queue, 'asdf');
	}
}
