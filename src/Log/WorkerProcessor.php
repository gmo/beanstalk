<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Log;

/**
 * Adds worker name to Monolog Logger.
 */
class WorkerProcessor
{
    /** @var string|null */
    protected $workerName;

    /**
     * Constructor.
     *
     * @param string|null $workerName
     */
    public function __construct(string $workerName = null)
    {
        $this->workerName = $workerName;
    }

    public function setName(string $workerName = null): void
    {
        $this->workerName = $workerName;
    }

    public function __invoke(array $record)
    {
        if ($this->workerName) {
            $record['extra']['worker'] = $this->workerName;
        }

        return $record;
    }
}
