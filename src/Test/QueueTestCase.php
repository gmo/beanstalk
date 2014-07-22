<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Queue;

/**
 * Class QueueTestCase
 * @package GMO\Beanstalk\Test
 * @since 1.7.0
 */
abstract class QueueTestCase extends \PHPUnit_Framework_TestCase {

	/** @var Queue */
	protected static $queue;
	protected static $clearTubesBeforeEveryTest = true;

	protected static function getHost() { return "127.0.0.1"; }
	protected static function getPort() { return 11300; }
	protected static function getLogger() { return null; }

	protected static function createQueueClass() {
		return new Queue(static::getHost(), static::getPort(), static::getLogger());
	}

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		static::$queue = static::createQueueClass();
		static::clearAllTubes();
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		static::clearAllTubes();
	}

	protected function setUp() {
		parent::setUp();
		if (static::$clearTubesBeforeEveryTest) {
			static::clearAllTubes();
		}
	}

	protected static function clearAllTubes() {
		foreach (static::$queue->listTubes() as $tube) {
			static::clearTube($tube);
		}
	}

	protected static function clearTube($tube) {
		static::$queue->deleteReadyJobs($tube);
		static::$queue->deleteDelayedJobs($tube);
		static::$queue->deleteBuriedJobs($tube);
	}

	protected static function assertTubeContains($value, $tube, $message = '', $ignoreCase = false) {
		static::assertContains($value, static::$queue->getReadyJobsIn($tube), $message, $ignoreCase);
	}

	protected static function assertTubeNotContains($value, $tube, $message = '', $ignoreCase = false) {
		static::assertNotContains($value, static::$queue->getReadyJobsIn($tube), $message, $ignoreCase);
	}

	protected static function assertTubeEquals($expected, $tube, $message = '', $ignoreCase = false, $delta = 0, $maxDepth = 10, $canonicalize = false) {
		static::assertEquals($expected, static::$queue->getReadyJobsIn($tube), $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
	}
}
