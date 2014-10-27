<?php

use GMO\Beanstalk\Test\QueueTestCase;
use GMO\Beanstalk\Test\TestRunner;
use GMO\Common\Collections\ArrayCollection;
use workers\UnitTestWorker;
use workers\UnitTestWorkerProcessGenericException;
use workers\UnitTestWorkerProcessJobError;
use workers\UnitTestWorkerSetupFailure;

class WorkerTest extends QueueTestCase {

	public function testWorkerPushesAndProcessesAJob() {
		$runner = new TestRunner(static::$queue, new UnitTestWorker());
		UnitTestWorker::push(static::$queue, array(
			'param1' => 'data1',
			'param2' => 'data2',
		));

		$this->assertTubeCount(1, UnitTestWorker::getTubeName());

		$job = $runner->run();

		$this->assertEquals(new ArrayCollection(array('data1', 'data2')), $job->getResult());
		$this->assertTubeEmpty(UnitTestWorker::getTubeName());
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Setup function failed!
	 */
	public function testWorkerSetupFails() {
		$runner = new TestRunner(static::$queue, new UnitTestWorkerSetupFailure());
		$runner->run();
	}

	public function testWorkerMissingParameters() {
		$runner = new TestRunner(static::$queue, new UnitTestWorker());
		UnitTestWorker::push(static::$queue, array(
			'param2' => 'data2',
		));
		$job = $runner->run();
		$this->assertNull($job->getResult());
		$this->assertTubeEmpty(UnitTestWorker::getTubeName());
	}

	public function testWorkerProcessingThrowsGenericException() {
		$runner = new TestRunner(static::$queue, new UnitTestWorkerProcessGenericException());
		UnitTestWorkerProcessGenericException::push(static::$queue, array(
			'param1' => 'dataA',
			'param2' => 'dataB',
		));
		$runner->run();

		$tube = static::$queue->getTube(UnitTestWorkerProcessGenericException::className());
		$this->assertCount(0, $tube->ready());
		$this->assertCount(1, $tube->buried());
	}

	public function testWorkerProcessingThrowsJobError() {
		$runner = new TestRunner(static::$queue, new UnitTestWorkerProcessJobError());
		$runner->run();
		$tube = static::$queue->getTube(UnitTestWorkerProcessJobError::className());
		$this->assertTrue($tube->isEmpty());
	}

}
