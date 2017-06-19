<?php

namespace GMO\Beanstalk\Job;

use Gmo\Common\Exception\NotSerializableException;

/**
 * A Job whose data is unable to be unserialized via {@see \Gmo\Common\Serialization\SerializableInterface}
 */
class UnserializableJob extends Job
{
    /** @var NotSerializableException */
    protected $exception;

    /**
     * @param int                      $id
     * @param string                   $data
     * @param JobControlInterface      $queue
     * @param NotSerializableException $exception
     */
    public function __construct($id, $data, JobControlInterface $queue, NotSerializableException $exception)
    {
        parent::__construct($id, $data, $queue);
        $this->exception = $exception;
    }

    /**
     * @return NotSerializableException
     */
    public function getException()
    {
        return $this->exception;
    }
}
