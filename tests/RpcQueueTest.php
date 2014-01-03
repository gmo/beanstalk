<?php

require_once __DIR__ . "/tester_autoload.php";

class RpcQueueTest extends \PHPUnit_Framework_TestCase {

	function test_rpc_call_timeout() {
		$log = new \Psr\Log\NullLogger();
		$q = \GMO\Beanstalk\RpcQueue::getInstance($log, HOST, PORT);
		
		try {
			$q->runRpc('queueWithNoWorkers', array(), 1);
		} catch(\GMO\Beanstalk\Exception\RpcTimeoutException $e) {
			$this->assertEquals('Timed Out', $e->getMessage());
			return;
		}
		
		$this->fail('Exception \GMO\Beanstalk\Exception\RpcTimeoutException was not thrown.');
	}

	static function tearDownAfterClass() {
		$log = new \Psr\Log\NullLogger();
		$q = \GMO\Beanstalk\Queue::getInstance($log, HOST, PORT);

		foreach ($q->listTubes() as $tube) {
			$q->deleteReadyJobs($tube);
			$q->deleteDelayedJobs($tube);
			$q->deleteBuriedJobs($tube);
		}
	}
}