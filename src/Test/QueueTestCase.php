<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\Job\Job;
use GMO\Common\Collections\ArrayCollection;

abstract class QueueTestCase extends \PHPUnit_Framework_TestCase {

	/** @var ArrayQueue */
	protected static $queue;
	protected static $clearTubesBeforeEveryTest = true;

	protected static function createQueueClass() {
		return new ArrayQueue();
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

	protected static function getJobs($tube) {
		return static::$queue->tube($tube)->ready();
	}

	protected static function assertTubeContains($value, $tube, $message = '', $ignoreCase = false) {
		$jobs = static::getJobs($tube);
		static::assertContains($value, $jobs, $message, $ignoreCase);
	}

	protected static function assertTubeNotContains($value, $tube, $message = '', $ignoreCase = false) {
		$jobs = static::getJobs($tube);
		static::assertNotContains($value, $jobs, $message, $ignoreCase);
	}

	protected static function assertTubeEquals($expected, $tube, $message = '', $ignoreCase = false, $delta = 0, $maxDepth = 10, $canonicalize = false) {
		$jobs = static::getJobs($tube);
		$jobs->map(function(Job $job) {
			return $job->getData();
		});
		$jobs = new ArrayCollection($jobs->toArray());
		$expected = new ArrayCollection($expected);
		static::assertEquals($expected, $jobs, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
	}

	protected static function assertTubeCount($expectedCount, $tube, $message = '') {
		static::assertCount($expectedCount, static::$queue->tube($tube)->ready(), $message);
	}

	protected static function assertTubeEmpty($tube, $message = '') {
		static::assertTubeCount(0, $tube, $message);
	}

	protected static function assertFirstJobParameter($expectedValue, $tube, $key, $message = '') {
		$job = static::getJobs($tube)->first();
		static::assertEquals($expectedValue, $job[$key], $message);
	}
}
