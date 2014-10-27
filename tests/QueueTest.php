<?php

use GMO\Beanstalk\Test\QueueTestCase;

require_once __DIR__ . "/tester_autoload.php";

class QueueTest extends QueueTestCase {

	function test_watch_only_tube_given() {
		static::$queue->push("test1", "test1data");
		static::$queue->push("test2", "test2data");
		static::$queue->push("test3", "test3data");

		$this->assertTubeEquals(array('test1data'), 'test1');
		$this->assertTubeEquals(array('test2data'), 'test2');
		$this->assertTubeEquals(array('test3data'), 'test3');
	}
}
