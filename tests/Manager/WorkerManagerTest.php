<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Manager;

use Gmo\Beanstalk\Manager\WorkerManager;
use Gmo\Beanstalk\Worker\WorkerInterface;
use PHPUnit\Framework\TestCase;

class WorkerManagerTest extends TestCase
{
    /** @var WorkerManager */
    private $wm;
    /** @var TestProcessor */
    private $processor;

    protected function setUp()
    {
        $dir = __DIR__ . '/../Worker/TestWorkers';
        $this->wm = new WorkerManager($dir);
        $this->processor = new TestProcessor($dir);
        $this->wm->setProcessor($this->processor);
    }

    public function testWorkerListAndCounts()
    {
        $actual = array();
        foreach ($this->wm->getWorkers() as $worker) {
            $actual[$worker->getName()] = array($worker->getNumRunning(), $worker->getTotal());
        }
        $expected = array(
            'NullWorker'                            => array(2, 3),
            'UnitTestRpcWorker'                     => array(1, 1),
            'UnitTestWorker'                        => array(0, 0),
            'UnitTestWorkerProcessGenericException' => array(0, 0),
            'UnitTestWorkerProcessJobError'         => array(0, 0),
            'UnitTestWorkerSetupFailure'            => array(0, 0),
        );
        $this->assertSame($expected, $actual);
    }

    public function testWorkersImplementInterface()
    {
        foreach ($this->wm->getWorkers() as $worker) {
            $this->assertTrue($worker->getInstance() instanceof WorkerInterface);
        }
    }

    public function testWorkersAreInstantiable()
    {
        foreach ($this->wm->getWorkers() as $worker) {
            $this->assertTrue($worker->getReflectionClass()->isInstantiable());
        }
    }

    public function testStopWorkers()
    {
        $this->wm->stopWorkers();
        $expectedPids = array(
            22923,
            22921,
            22925,
        );
        $this->assertSame($expectedPids, $this->processor->terminatedProcesses->toArray());
        $this->assertSame($expectedPids, $this->processor->waitedForProcesses->toArray());
        $this->assertSame($expectedPids, $this->processor->terminatedForcefullyProcesses->toArray());
    }

    public function testStartWorkers()
    {
        $this->wm->startWorkers();
        $this->assertCount(1, $this->processor->executeCalls);
        $this->assertContains('NullWorker', $this->processor->executeCalls->first());
    }
}
