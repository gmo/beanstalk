<?php

namespace Gmo\Beanstalk\Tests\Worker;

use Gmo\Beanstalk\Manager\WorkerManager;
use Gmo\Beanstalk\Queue\Queue;
use Gmo\Beanstalk\Test\QueueTestCase;
use Gmo\Beanstalk\Tests\Worker\TestWorkers\UnitTestRpcWorker;

class RpcTest extends QueueTestCase
{
    public function test_rpc()
    {
        $data = array('a' => 2, 'b' => 2);
        $manager = new WorkerManager(__DIR__ . '/TestWorkers');
        $manager->startWorkers('UnitTestRpcWorker');
        $result = UnitTestRpcWorker::runRpc(new Queue(), $data);
        $this->assertSame(4, $result);
    }

    /**
     * @expectedException \Gmo\Beanstalk\Exception\RpcTimeoutException
     */
    public function test_rpc_call_timeout()
    {
        UnitTestRpcWorker::runRpc(static::$queue, 'asdf');
    }
}
