<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job;

use Bolt\Common\Exception\ParseException;

/**
 * A Job whose data is unable to be parsed.
 */
class UnserializableJob extends Job
{
    /** @var ParseException */
    protected $exception;

    /**
     * Constructor.
     *
     * @param int                 $id
     * @param string              $data
     * @param JobControlInterface $queue
     * @param ParseException      $exception
     */
    public function __construct($id, $data, JobControlInterface $queue, ParseException $exception)
    {
        parent::__construct($id, $data, $queue);
        $this->exception = $exception;
    }

    public function getException(): ParseException
    {
        return $this->exception;
    }
}
