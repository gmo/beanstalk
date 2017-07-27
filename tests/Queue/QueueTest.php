<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Queue;

use Gmo\Beanstalk\Queue\Queue;
use Gmo\Beanstalk\Test\ArrayQueue;
use Gmo\Beanstalk\Test\QueueTestCase;

class QueueTest extends QueueTestCase
{
    /** @var Queue */
    protected static $queue;

    public function testKickAllBuriedAndDelayed()
    {
        $this->pushBuriedAndDelayedJobs();

        $numKicked = static::$queue->kickTube('test1');
        $this->assertSame(4, $numKicked);

        $stats = static::$queue->statsTube('test1');
        $this->assertSame(4, $stats->readyJobs());
        $this->assertSame(0, $stats->buriedJobs());
        $this->assertSame(0, $stats->delayedJobs());
    }

    public function testKickSomeBuriedWithSpecifiedNumber()
    {
        $this->pushBuriedAndDelayedJobs();

        $numKicked = static::$queue->kickTube('test1', 1);
        $this->assertSame(1, $numKicked);

        $stats = static::$queue->statsTube('test1');
        $this->assertSame(1, $stats->readyJobs());
        $this->assertSame(1, $stats->buriedJobs());
        $this->assertSame(2, $stats->delayedJobs());
    }

    public function testKickAllBuriedWithSpecifiedNumber()
    {
        $this->pushBuriedAndDelayedJobs();

        $numKicked = static::$queue->kickTube('test1', 2);
        $this->assertSame(2, $numKicked);

        $stats = static::$queue->statsTube('test1');
        $this->assertSame(2, $stats->readyJobs());
        $this->assertSame(0, $stats->buriedJobs());
        $this->assertSame(2, $stats->delayedJobs());
    }

    public function testKickAllBuriedAndSomeDelayedWithSpecifiedNumber()
    {
        $this->pushBuriedAndDelayedJobs();

        $numKicked = static::$queue->kickTube('test1', 3);
        $this->assertSame(3, $numKicked);

        $stats = static::$queue->statsTube('test1');
        $this->assertSame(3, $stats->readyJobs());
        $this->assertSame(0, $stats->buriedJobs());
        $this->assertSame(1, $stats->delayedJobs());
    }

    public function testKickAllBuriedAndAllDelayedWithSpecifiedNumber()
    {
        $this->pushBuriedAndDelayedJobs();

        $numKicked = static::$queue->kickTube('test1', 4);
        $this->assertSame(4, $numKicked);

        $stats = static::$queue->statsTube('test1');
        $this->assertSame(4, $stats->readyJobs());
        $this->assertSame(0, $stats->buriedJobs());
        $this->assertSame(0, $stats->delayedJobs());
    }

    protected function pushBuriedAndDelayedJobs()
    {
        static::$queue->push('test1', 'test');
        static::$queue->reserve('test1')->bury();
        static::$queue->push('test1', 'test');
        static::$queue->reserve('test1')->bury();
        static::$queue->push('test1', 'test');
        static::$queue->reserve('test1')->release(3600);
        static::$queue->push('test1', 'test');
        static::$queue->reserve('test1')->release(3600);
    }

    protected static function createQueueClass()
    {
        //return new Queue();
        return new ArrayQueue();
    }
}
