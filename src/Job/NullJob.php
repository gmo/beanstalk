<?php

namespace GMO\Beanstalk\Job;

use GMO\Beanstalk\Queue\Response\JobStats;

class NullJob extends Job
{
    public function __construct()
    {
    }

    public function getId()
    {
        return -1;
    }

    public function setData($data)
    {
    }

    public function getData()
    {
        return null;
    }

    public function setResult($result)
    {
    }

    public function getResult()
    {
        return null;
    }

    public function isHandled()
    {
        return true;
    }

    public function release($delay = null, $priority = null)
    {
    }

    public function bury()
    {
    }

    public function delete()
    {
    }

    public function kick()
    {
    }

    public function touch()
    {
    }

    public function stats()
    {
        return new JobStats();
    }

    public function offsetExists($offset)
    {
        return false;
    }

    public function offsetGet($offset)
    {
        return null;
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

    public function getIterator()
    {
        return new \EmptyIterator();
    }
}
