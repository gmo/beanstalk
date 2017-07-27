<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job;

use Bolt\Common\Assert;

class Job implements \ArrayAccess, \IteratorAggregate
{
    /** @var int */
    protected $id;
    /** @var mixed */
    protected $data;
    /** @var JobControlInterface */
    protected $queue;
    /** @var bool */
    protected $handled = false;
    /** @var mixed|null */
    protected $result;

    public function __construct($id, $data, JobControlInterface $queue)
    {
        $this->id = $id;
        $this->data = $data;
        $this->queue = $queue;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns whether the job has been handled (released, buried, deleted).
     *
     * @return bool
     */
    public function isHandled()
    {
        return $this->handled;
    }

    //region Job Control Methods

    /**
     * @param int $delay    Seconds to wait before job becomes ready
     * @param int $priority From 0 (most urgent) to 4294967295 (least urgent)
     */
    public function release($delay = null, $priority = null)
    {
        $this->handled = true;
        $this->queue->release($this, $priority, $delay);
    }

    public function bury()
    {
        $this->handled = true;
        $this->queue->bury($this);
    }

    public function delete()
    {
        $this->handled = true;
        $this->queue->delete($this);
    }

    public function kick()
    {
        $this->queue->kickJob($this);
    }

    public function touch()
    {
        $this->queue->touch($this);
    }

    public function stats()
    {
        return $this->queue->statsJob($this);
    }

    //endregion

    //region Array and Iterator Methods

    /** @inheritdoc */
    public function offsetExists($offset)
    {
        $this->assertArrayAccess();

        return isset($this->data[$offset]);
    }

    /** @inheritdoc */
    public function offsetGet($offset)
    {
        $this->assertArrayAccess();

        return $this->data[$offset] ?? null;
    }

    /** @inheritdoc */
    public function offsetSet($offset, $value)
    {
        $this->assertArrayAccess();

        $this->data[$offset] = $value;
    }

    /** @inheritdoc */
    public function offsetUnset($offset)
    {
        $this->assertArrayAccess();

        unset($this->data[$offset]);
    }

    protected function assertArrayAccess()
    {
        try {
            Assert::isArrayAccessible($this->data, 'Cannot use Array Access methods with type %s');
        } catch (\InvalidArgumentException $e) {
            throw new \LogicException($e->getMessage());
        }
    }

    /** @inheritdoc */
    public function getIterator()
    {
        if ($this->data instanceof \Traversable) {
            return new \IteratorIterator($this->data);
        }
        if (is_array($this->data)) {
            return new \ArrayIterator($this->data);
        }

        return new \ArrayIterator([
            'data' => $this->data,
        ]);
    }

    //endregion
}
