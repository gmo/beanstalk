<?php

use GMO\Beanstalk\Queue;
use Psr\Log\NullLogger;
use workers\UnitTestWorker;
use workers\UnitTestWorkerProcessFails;
use workers\UnitTestWorkerSetupFailure;

require_once __DIR__ . "/tester_autoload.php";

class given_the_worker_runs_with_one_message_in_the_queue_and_message_has_valid_params extends ContextSpecification {
	public static function given() {
		$logger = new NullLogger();
		self::$queue = Queue::getInstance( $logger, HOST, PORT );

		self::$queue->deleteReadyJobs( UnitTestWorker::getTubeName() );
		self::$queue->push( UnitTestWorker::getTubeName(), array( "param1" => "data1", "param2" => "data2" ) );

		self::$sut = new UnitTestWorker();
		self::$sut->setToRunOnce();

	}

	public static function when() {
		self::$sut->run( HOST, PORT );
	}

	public function test_process_ran_successfully() {
		$this->assertEquals(
		     json_encode( array( "param1" => "data1", "param2" => "data2" ) ),
		     self::$sut->processResult
		);
	}

	public function test_the_queue_is_empty() {
		$this->assertEquals( 0, self::$queue->getNumberOfJobsReady( UnitTestWorker::getTubeName() ) );
	}

	/**
	 * @var Queue
	 */
	private static $queue;
	/**
	 * @var UnitTestWorker
	 */
	private static $sut;
}

class given_the_worker_runs_with_one_message_in_the_queue_and_the_setup_fails extends ContextSpecification {
	public static function given() {
		$logger = new NullLogger();
		self::$queue = Queue::getInstance( $logger, HOST, PORT );

		self::$queue->deleteReadyJobs( UnitTestWorkerSetupFailure::getTubeName() );
		self::$queue->push(
		            UnitTestWorkerSetupFailure::getTubeName(),
		            array( "param1" => "data1", "param2" => "data2" )
		);

		self::$sut = new UnitTestWorkerSetupFailure();
		self::$sut->setToRunOnce();
	}

	public static function cleanUp() {
		self::$queue->deleteReadyJobs( UnitTestWorkerSetupFailure::getTubeName() );
	}

	public function test_an_exception_is_thrown() {
		try {
			self::$sut->run( HOST, PORT );
		} catch ( \Exception $expected ) {
			return;
		}

		$this->fail( "An expected Exception has not been raised." );
	}

	public function test_the_queue_still_contains_a_job() {
		$this->assertEquals( 1, self::$queue->getNumberOfJobsReady( UnitTestWorker::getTubeName() ) );
	}

	/**
	 * @var Queue
	 */
	private static $queue;
	/**
	 * @var UnitTestWorkerSetupFailure
	 */
	private static $sut;
}

class given_the_worker_runs_with_one_message_in_the_queue_and_message_has_missing_params extends ContextSpecification {
	public static function given() {
		$logger = new NullLogger();
		self::$queue = Queue::getInstance( $logger, HOST, PORT );

		self::$queue->deleteReadyJobs( UnitTestWorker::getTubeName() );
		self::$queue->push( UnitTestWorker::getTubeName(), array( "param2" => "data2" ) );

		self::$sut = new UnitTestWorker();
		self::$sut->setToRunOnce();

	}

	public static function when() {
		self::$sut->run( HOST, PORT );
	}

	public function test_process_was_not_run() {
		$this->assertEquals( null, self::$sut->processResult );
	}

	public function test_the_job_was_deleted() {
		$this->assertEquals( 0, self::$queue->getNumberOfJobsReady( UnitTestWorker::getTubeName() ) );
	}

	/**
	 * @var Queue
	 */
	private static $queue;
	/**
	 * @var UnitTestWorker
	 */
	private static $sut;
}

class given_the_worker_runs_with_one_message_in_the_queue_and_process_fails extends ContextSpecification {
	public static function given() {
		$logger = new NullLogger();
		self::$queue = Queue::getInstance( $logger, HOST, PORT );

		self::$queue->deleteReadyJobs( UnitTestWorkerProcessFails::getTubeName() );
		self::$queue->push(
		            UnitTestWorkerProcessFails::getTubeName(),
		            array( "param1" => "data1", "param2" => "data2" )
		);

		self::$sut = new UnitTestWorkerProcessFails();
		self::$sut->setToRunOnce();

	}

	public static function when() {
	}

	public function test_process_is_run_3_times_before_job_is_deleted() {
		self::$sut->run( HOST, PORT );
		$this->assertEquals( 1, self::$sut->getNumberOfErrorsForCurrentJob() );
		self::$queue->kickBuriedJobs( UnitTestWorkerProcessFails::getTubeName() );

		self::$sut->run( HOST, PORT );
		$this->assertEquals( 2, self::$sut->getNumberOfErrorsForCurrentJob() );
		self::$queue->kickBuriedJobs( UnitTestWorkerProcessFails::getTubeName() );

		self::$sut->run( HOST, PORT );
		$this->assertEquals( 3, self::$sut->getNumberOfErrorsForCurrentJob() );
		self::$queue->kickBuriedJobs( UnitTestWorkerProcessFails::getTubeName() );

		$this->assertEquals( 0, self::$queue->getNumberOfJobsReady( UnitTestWorkerProcessFails::getTubeName() ) );
	}

	/**
	 * @var Queue
	 */
	private static $queue;
	/**
	 * @var UnitTestWorkerProcessFails
	 */
	private static $sut;
}
