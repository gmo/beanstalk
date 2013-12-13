<?php

use GMO\Beanstalk\Queue;
use GMO\Common\AbstractSerializable;
use Psr\Log\NullLogger;
use workers\TestSerializableWorker;

require_once __DIR__ . "/tester_autoload.php";

class SerializableWorkerTest extends \PHPUnit_Framework_TestCase {

	public function test_valid_object() {
		$derp = new Derp("test");
		$this->push($derp->toArray());

		$this->sut->run(HOST, PORT);

		$this->assertSame(
		     array( "class" => "Derp", "herp" => "test" ),
		     $this->sut->processResult
		);
	}

	public function test_params_are_missing_class_attribute() {
		$derp = new Derp("test");
		$params = $derp->toArray();
		unset($params["class"]);
		$this->push($params);

		$this->sut->run(HOST, PORT);

		$this->assertCount(1, $this->sut->getJobErrors());
	}

	public function test_class_param_does_not_implement_ISerializable() {
		$this->push(array( "class" => "Herp", "duh" => "winning" ));

		$this->sut->run(HOST, PORT);

		$this->assertCount(1, $this->sut->getJobErrors());
	}

	protected function setUp() {
		$this->queue = Queue::getInstance( new NullLogger(), HOST, PORT );
		$this->queue->deleteReadyJobs( TestSerializableWorker::getTubeName() );

		$this->sut = new TestSerializableWorker();
		$this->sut->setToRunOnce();
	}

	private function push($data) {
		$this->queue->push(TestSerializableWorker::getTubeName(), $data);
	}

	/** @var TestSerializableWorker */
	private $sut;
	/** @var Queue */
	private $queue;
}

class Herp { }

class Derp extends AbstractSerializable {

	public function __construct($herp) {
		$this->herp = $herp;
	}
	protected $herp;
}