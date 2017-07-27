<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Job;

use Pheanstalk\PheanstalkInterface;

interface JobProducerInterface
{
    public const HIGH_PRIORITY = 512;
    public const DEFAULT_PRIORITY = 1024;
    public const LOW_PRIORITY = 1536;

    public const DEFAULT_DELAY = PheanstalkInterface::DEFAULT_DELAY;
    public const DEFAULT_TTR = PheanstalkInterface::DEFAULT_TTR;

    /**
     * Pushes a job to the specified tube.
     *
     * @param string   $tube     Tube name
     * @param mixed    $data     Job data (needs to be serializable)
     * @param int|null $priority From 0 (most urgent) to 4294967295 (least urgent)
     * @param int|null $delay    Seconds to wait before job becomes ready
     * @param int|null $ttr      Time To Run: seconds a job can be reserved for
     *
     * @return int The new job ID
     */
    public function push($tube, $data, $priority = null, $delay = null, $ttr = null);
}
