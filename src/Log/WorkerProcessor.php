<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Log;

/**
 * Adds worker name to Monolog Logger.
 */
class WorkerProcessor
{
    /** @var string */
    protected $workerName;

    /**
     * Constructor.
     *
     * @param string $workerName
     */
    public function __construct($workerName)
    {
        $this->workerName = $workerName;
    }

    public function __invoke(array $record)
    {
        $record['extra']['worker'] = $this->workerName;

        return $record;
    }
}
