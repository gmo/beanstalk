<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Worker;

use Bolt\Collection\Bag;
use Gmo\Beanstalk\Test\QueueTestCase;
use Gmo\Beanstalk\Test\TestRunner;
use Gmo\Beanstalk\Tests\Worker\TestWorkers\UnitTestWorker;
use Gmo\Beanstalk\Tests\Worker\TestWorkers\UnitTestWorkerProcessGenericException;
use Gmo\Beanstalk\Tests\Worker\TestWorkers\UnitTestWorkerProcessJobError;
use Gmo\Beanstalk\Tests\Worker\TestWorkers\UnitTestWorkerSetupFailure;

class WorkerTest extends QueueTestCase
{
    public function testWorkerPushesAndProcessesAJob()
    {
        $runner = new TestRunner(static::$queue, new UnitTestWorker());
        UnitTestWorker::pushData(static::$queue, array(
            'param1' => 'data1',
            'param2' => 'data2',
        ));

        $this->assertTubeCount(1, UnitTestWorker::getTubeName());

        $job = $runner->run();

        $this->assertEquals(new Bag(array('data1', 'data2')), $job->getResult());
        $this->assertTubeEmpty(UnitTestWorker::getTubeName());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Setup function failed!
     */
    public function testWorkerSetupFails()
    {
        $runner = new TestRunner(static::$queue, new UnitTestWorkerSetupFailure());
        $runner->run();
    }

    public function testWorkerMissingParameters()
    {
        $runner = new TestRunner(static::$queue, new UnitTestWorker());
        UnitTestWorker::pushData(static::$queue, array(
            'param2' => 'data2',
        ));
        $job = $runner->run();
        $this->assertNull($job->getResult());
        $this->assertTubeEmpty(UnitTestWorker::getTubeName());
    }

    public function testWorkerProcessingThrowsGenericException()
    {
        $runner = new TestRunner(static::$queue, new UnitTestWorkerProcessGenericException());
        UnitTestWorkerProcessGenericException::pushData(static::$queue, array(
            'param1' => 'dataA',
            'param2' => 'dataB',
        ));
        $runner->run();

        $tube = static::$queue->tube(UnitTestWorkerProcessGenericException::getTubeName());
        $this->assertCount(0, $tube->ready());
        $this->assertCount(1, $tube->buried());
    }

    public function testWorkerProcessingThrowsJobError()
    {
        $runner = new TestRunner(static::$queue, new UnitTestWorkerProcessJobError());
        $runner->run();
        $tube = static::$queue->tube(UnitTestWorkerProcessJobError::getTubeName());
        $this->assertTrue($tube->isEmpty());
    }
}
