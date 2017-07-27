<?php

namespace Gmo\Beanstalk\Exception;

use Exception;

/**
 * Exceptions when failing to push jobs to Queue
 */
class JobPushException extends QueueException
{
    protected $tube;
    protected $jobData;

    /**
     * @param string    $tube
     * @param mixed     $jobData
     * @param Exception $previous
     */
    public function __construct($tube, $jobData, Exception $previous)
    {
        parent::__construct($previous->getMessage(), 0, $previous);
        $this->tube = $tube;
        $this->jobData = $jobData;
    }

    /** @return string */
    public function getTube()
    {
        return $this->tube;
    }

    /** @return string */
    public function getJobData()
    {
        return $this->jobData;
    }
}
