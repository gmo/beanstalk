<?php

require_once __DIR__ . "/tester_autoload.php";

class QueueTest extends \PHPUnit_Framework_TestCase {

	function test_watch_only_tube_given() {
		$log = new \Psr\Log\NullLogger();
		$q = \GMO\Beanstalk\Queue::getInstance($log, HOST, PORT);
		$q->push("test1", "test1data");
		$q->push("test2", "test2data");
		$q->push("test3", "test3data");

		$this->assertEquals(array( '"test1data"' ), $q->getReadyJobsIn("test1"));
		$this->assertEquals(array( '"test2data"' ), $q->getReadyJobsIn("test2"));
		$this->assertEquals(array( '"test3data"' ), $q->getReadyJobsIn("test3"));

		foreach ($q->listTubes() as $tube) {
			$q->deleteReadyJobs($tube);
			$q->deleteDelayedJobs($tube);
			$q->deleteBuriedJobs($tube);
		}
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