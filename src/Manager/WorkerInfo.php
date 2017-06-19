<?php

namespace GMO\Beanstalk\Manager;

use Bolt\Collection\Bag;
use Bolt\Collection\ImmutableBag;
use GMO\Beanstalk\Worker\WorkerInterface;

class WorkerInfo
{
    /** @var \ReflectionClass */
    private $refCls;
    /** @var WorkerInterface */
    private $instance;
    /** @var Bag */
    private $pids;

    public function __construct(\ReflectionClass $reflectionClass)
    {
        $this->refCls = $reflectionClass;
        $this->pids = new Bag();
    }

    /** @return string Fully qualified class name */
    public function getFullyQualifiedName()
    {
        return $this->refCls->getName();
    }

    /** @return string class name without namespace */
    public function getName()
    {
        return $this->refCls->getShortName();
    }

    /** @return int number of workers currently running */
    public function getNumRunning()
    {
        return count($this->pids);
    }

    /** @return int total number of workers */
    public function getTotal()
    {
        return $this->getInstance()->getNumberOfWorkers();
    }

    public function getReflectionClass()
    {
        return $this->refCls;
    }

    /** @return \GMO\Beanstalk\Worker\WorkerInterface */
    public function getInstance()
    {
        if (!$this->instance) {
            $this->instance = $this->refCls->newInstance();
        }

        return $this->instance;
    }

    /** @return int[]|ImmutableBag */
    public function getPids()
    {
        return $this->pids->immutable();
    }

    /**
     * @param int $pid
     */
    public function addPid($pid)
    {
        $this->pids[] = (int) $pid;
    }

    /**
     * @param int $pid
     */
    public function removePid($pid)
    {
        $this->pids->removeItem($pid);
    }
}
