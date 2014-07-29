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

	protected static function getJobs($tube, $decode = true) {
		$jobs = static::$queue->getReadyJobsIn($tube);
		if ($decode) {
			$jobs = array_map(function($job) { return json_decode($job, true); }, $jobs);
		}
		return $jobs;
	}

	protected static function getFirstJob($tube, $decode = true) {
		$jobs = static::getJobs($tube, $decode);
		return $jobs[0];
	}

	protected static function assertTubeContains($value, $tube, $message = '', $ignoreCase = false) {
		$jobs = static::getJobs($tube, is_array($value));
		static::assertContains($value, $jobs, $message, $ignoreCase);
	}

	protected static function assertTubeNotContains($value, $tube, $message = '', $ignoreCase = false) {
		$jobs = static::getJobs($tube, is_array($value));
		static::assertNotContains($value, $jobs, $message, $ignoreCase);
	}

	protected static function assertTubeEquals($expected, $tube, $message = '', $ignoreCase = false, $delta = 0, $maxDepth = 10, $canonicalize = false) {
		$jobs = static::getJobs($tube, is_array($expected[0]));
		static::assertEquals($expected, $jobs, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
	}

	protected static function assertTubeCount($expectedCount, $tube, $message = '') {
		static::assertCount($expectedCount, static::$queue->getReadyJobsIn($tube), $message);
	}

	protected static function assertTubeEmpty($tube, $message = '') {
		static::assertTubeCount(0, $tube, $message);
	}

	protected static function assertFirstJobParameter($expectedValue, $tube, $key, $message = '') {
		$job = static::getFirstJob($tube);
		static::assertEquals($expectedValue, $job[$key], $message);
	}
}
