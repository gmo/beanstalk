<?php

use GMO\Beanstalk\Helper\Processor;
use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Beanstalk\Worker\WorkerInterface;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\String;

class WorkerManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkerManager */
    private $wm;
    /** @var TestProcessor */
    private $processor;

    protected function setUp()
    {
        $dir = realpath('workers') . '/';
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
    }

    public function testStartWorkers()
    {
        $this->wm->startWorkers();
        $this->assertCount(1, $this->processor->executeCalls);
        $this->assertContains('NullWorker', $this->processor->executeCalls->first());
    }
}

class TestProcessor extends Processor
{
    private $workerDir;
    public $executeCalls;
    public $terminatedProcesses;
    public $waitedForProcesses;

    public function __construct($workerDir)
    {
        $this->workerDir = realpath($workerDir) . '/';
        $this->executeCalls = new ArrayCollection();
        $this->terminatedProcesses = new ArrayCollection();
        $this->waitedForProcesses = new ArrayCollection();
    }

    public function waitForProcess($pid, $interval = 200)
    {
        $this->waitedForProcesses->add($pid);
    }

    public function isProcessRunning($pid)
    {
        return $this->getProcesses()
            ->exists(function ($key, $value) use ($pid) {
                return $value[1] == $pid;
            })
        ;
    }

    public function terminateProcess($pid)
    {
        $this->terminatedProcesses->add($pid);
    }

    public function grepForPids($grep)
    {
        $grep = str_replace('\"', '"', $grep);

        return $this->getProcessLines()
            ->filter(function ($line) use ($grep) {
                return String::contains($line, $grep, false);
            })
            ->map(array($this, 'parseLines'))
            ->map(function ($line) {
                return array($line[13], $line[1]);
            })
        ;
    }

    public function execute($command, array &$output = null, &$return_var = null)
    {
        $this->executeCalls->add($command);
    }

    private function getProcessLines()
    {
        $workerDir = $this->workerDir;

        return ArrayCollection::create(file(__DIR__ . '/process_list.txt'))
            ->map(function ($line) use ($workerDir) {
                return str_replace('{WORKER_DIR}', $workerDir, $line);
            })
        ;
    }

    public function parseLines($line)
    {
        if (preg_match_all('/"[^"]+"|\S+/', $line, $matches)) {
            return $matches[0];
        }

        return $line;
    }

    private function getProcesses()
    {
        return $this->getProcessLines()
            ->map(function ($line) {
                if (preg_match_all('/"[^"]+"|\S+/', $line, $matches)) {
                    return $matches[0];
                }

                return $line;
            })
        ;
    }
}
