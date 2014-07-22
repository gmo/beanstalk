<?php

use GMO\Beanstalk\Test\RpcQueueTestCase;

require_once __DIR__ . "/tester_autoload.php";

class RpcQueueTest extends RpcQueueTestCase {

	function test_rpc_call_timeout() {
		try {
			static::$queue->runRpc('queueWithNoWorkers', array(), 1);
		} catch(\GMO\Beanstalk\Exception\RpcTimeoutException $e) {
			$this->assertEquals('Timed Out', $e->getMessage());
			return;
		}
		
		$this->fail('Exception \GMO\Beanstalk\Exception\RpcTimeoutException was not thrown.');
	}
}
