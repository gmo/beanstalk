<?php

use GMO\Beanstalk\WorkerManager;
use GMO\Beanstalk\RpcQueue;
use Psr\Log\NullLogger;
use workers\UnitTestRpcWorker;

require_once __DIR__ . "/tester_autoload.php";

class given_the_worker_runs_when_one_message_is_put_in_the_queue extends ContextSpecification {
	public static function given() {
		$logger = new NullLogger();
		static::$queue = RpcQueue::getInstance( $logger, HOST, PORT );
		static::$manager = new WorkerManager( WORKER_DIR, $logger, HOST, PORT );
	}

	public static function when() {
		static::$manager->startWorker('UnitTestRpcWorker');
	}

	public function test_rpc_ran_successfully() {
		$data = array('a' => 2, 'b' => 2);
		
		$results = static::$queue->runRpc(UnitTestRpcWorker::getTubeName(), $data, 5);
		
		$this->assertEquals(4, $results);
	}

	/**
	 * @var RpcQueue
	 */
	private static $queue;

	/**
	 * @var UnitTestWorkerManager
	 */
	private static $manager;
}
