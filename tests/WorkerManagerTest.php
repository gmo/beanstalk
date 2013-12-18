<?php

use Psr\Log\NullLogger;

require_once __DIR__ . "/tester_autoload.php";

class When_restarting_workers extends ContextSpecification {

	protected static function given() {
		$log = new NullLogger();
		self::$workerManager = new UnitTestWorkerManagerRestart(WORKER_DIR, $log, HOST, PORT);
	}

	protected static function when() {
		self::$workerManager->restartWorkers();
	}

	public function test_workers_should_be_stopped() {
		$this->assertTrue( self::$workerManager->stopCalled );
	}

	public function test_workers_should_be_started() {
		$this->assertTrue( self::$workerManager->startCalled );
	}

	/**
	 * @var UnitTestWorkerManagerRestart
	 */
	private static $workerManager;
}

class Given_there_are_workers_running_when_stop_workers_is_called extends ContextSpecification {

	protected static function given() {
		$log = new NullLogger();
		self::$workerManager = new UnitTestWorkerManager(WORKER_DIR, $log, HOST, PORT);
	}

	protected static function when() {
		self::$workerManager->stopWorkers();
	}

	public function test_all_workers_are_stopped() {
		$correctCommands = array(
			"kill 14692",
			"kill 14693",
			"kill 14690"
		);
		foreach ( $correctCommands as $cmd ) {
			$this->assertContains( $cmd, self::$workerManager->calledCommands );
		}
	}

	/**
	 * @var UnitTestWorkerManager
	 */
	private static $workerManager;
}

class Given_workers_are_currently_running extends ContextSpecification {

	protected static function given() {
		$log = new NullLogger();
		self::$workerManager = new UnitTestWorkerManager(WORKER_DIR, $log, HOST, PORT);
	}

	protected static function when() {
		self::$currentlyRunningWorkers = self::$workerManager->getRunningWorkers();
	}

	public function test_list_workers_correctly() {
		$correctWorkers = array(
			"NullWorker"     => 1,
			"UnitTestWorker" => 2
		);
		$this->assertEquals( $correctWorkers, self::$currentlyRunningWorkers );
	}

	/**
	 * @var UnitTestWorkerManager
	 */
	private static $workerManager;
	private static $currentlyRunningWorkers;
}

class Given_a_directory_get_number_of_workers extends ContextSpecification {

	protected static function given() {
		$log = new NullLogger();
		self::$workerManager = new UnitTestWorkerManager(WORKER_DIR, $log, HOST, PORT);
	}

	protected static function when() {
		self::$workers = self::$workerManager->getWorkers();
	}

	public function test_numbers_are_zero() {
		$correctWorkers = array(
			"NullWorker"                                     => 3,
			"UnitTestWorker"                                 => 1,
			"UnitTestWorkerProcessGenericException"          => 0,
			"UnitTestWorkerProcessJobAwareException"         => 0,
			"UnitTestWorkerProcessJobAwareExceptionNoDelete" => 0,
			"UnitTestWorkerSetupFailure"                     => 0
		);
		foreach ( $correctWorkers as $workerName => $workerCount ) {
			$this->assertEquals(
			     $workerCount,
			     self::$workers[$workerName]->getNumberOfWorkers()
			);
		}
	}

	/**
	 * @var UnitTestWorkerManager
	 */
	private static $workerManager;
	private static $workers;
}

class Given_a_directory_get_workers extends ContextSpecification {

	protected static function given() {
		$log = new NullLogger();
		self::$workerManager = new UnitTestWorkerManager(WORKER_DIR, $log, HOST, PORT);
	}

	protected static function when() {
		self::$workers = self::$workerManager->getWorkers();
	}

	public function test_abstract_worker_not_included() {
		$this->assertArrayNotHasKey( "AbstractWorker", self::$workers );
	}

	public function test_workers_are_subclasses_of_abstract_worker() {
		foreach ( self::$workers as $worker ) {
			$this->assertInstanceOf( "\\GMO\\Beanstalk\\AbstractWorker", $worker );
		}
	}

	public function test_workers_are_instantiable() {
		foreach ( self::$workers as $worker ) {
			$cls = new ReflectionClass($worker);
			$this->assertTrue($cls->isInstantiable());
		}
	}

	/**
	 * @var UnitTestWorkerManager
	 */
	private static $workerManager;
	private static $workers;
}

class Given_a_directory_start_workers extends ContextSpecification {

	protected static function given() {
		$log = new NullLogger();
		self::$workerManager = new UnitTestWorkerManager(WORKER_DIR, $log, HOST, PORT);
	}

	protected static function when() {
		self::$workerManager->startWorkers();
	}

	public function test_start_only_needed_count() {
		$expected = array( "NullWorker" => 2 );
		$this->assertEquals( $expected, self::$workerManager->calledWorkers );
	}

	/**
	 * @var UnitTestWorkerManager
	 */
	private static $workerManager;
}
