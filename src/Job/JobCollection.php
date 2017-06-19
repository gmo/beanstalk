<?php

namespace GMO\Beanstalk\Job;

use Bolt\Collection\Bag;
use GMO\Beanstalk\Test\ArrayJob;

class JobCollection extends Bag
{
    /**
     * @inheritdoc
     * @return Job
     */
    public function get($key, $default = null)
    {
        return parent::get($key, $default) ?: new NullJob();
    }

    /**
     * @inheritdoc
     * @return Job
     */
    public function first()
    {
        return parent::first() ?: new NullJob();
    }

    /**
     * @inheritdoc
     * @return Job
     */
    public function last()
    {
        return parent::last() ?: new NullJob();
    }

    /**
     * @inheritdoc
     * @return Job
     */
    public function &offsetGet($offset)
    {
        return parent::offsetGet($offset) ?: new NullJob();
    }

    /**
     * @inheritdoc
     * @return Job
     */
    public function remove($key, $default = null)
    {
        return parent::remove($key) ?: new NullJob();
    }

    /**
     * @inheritdoc
     * @return Job
     */
    public function removeFirst()
    {
        return parent::removeFirst() ?: new NullJob();
    }

    /**
     * @inheritdoc
     * @return Job
     */
    public function removeLast()
    {
        return parent::removeLast() ?: new NullJob();
    }

    /**
     * Prioritize ArrayJobs.
     */
    public function prioritize()
    {
        usort($this->items, function (ArrayJob $a, ArrayJob $b) {
            if ($a->getPriority() === $b->getPriority()) {
                return $this->indexOf($a) <=> $this->indexOf($b);
            }

            return $a->getPriority() <=> $b->getPriority();
        });
    }
}
