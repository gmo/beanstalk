<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Queue;

use Bolt\Collection\Bag;
use Gmo\Beanstalk\Job\JobControlInterface;
use Gmo\Beanstalk\Queue\Response\ServerStats;
use Gmo\Beanstalk\Queue\Response\TubeStats;
use Gmo\Beanstalk\Tube\Tube;
use Gmo\Beanstalk\Tube\TubeCollection;
use Gmo\Beanstalk\Tube\TubeControlInterface;
use Psr\Log\LoggerAwareInterface;

interface QueueInterface extends TubeControlInterface, JobControlInterface, LoggerAwareInterface
{
    /**
     * Gets a tube by name.
     *
     * @param string $name
     *
     * @return Tube
     */
    public function tube($name);

    /**
     * Gets a list of all the tubes.
     *
     * @return TubeCollection|Tube[]
     */
    public function tubes();

    /**
     * Gets the stats for all tubes.
     *
     * @return TubeStats[]|Bag
     */
    public function statsAllTubes();

    /**
     * Returns the stats about the server.
     *
     * @return ServerStats
     */
    public function statsServer();

    /**
     * Inspect a job in the system by ID.
     *
     * @param int $jobId
     *
     * @return \Gmo\Beanstalk\Job\Job
     */
    public function peekJob($jobId);
}
