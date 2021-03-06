<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Queue;

interface QueueAwareInterface
{
    /**
     * Sets the queue instance.
     *
     * @param QueueInterface $queue
     */
    public function setQueue(QueueInterface $queue);
}
