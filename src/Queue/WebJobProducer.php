<?php

namespace Gmo\Beanstalk\Queue;

use Gmo\Beanstalk\Job\JobProducerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * WebJobProducer is designed for producing jobs within a web request context.
 * All exceptions will be logged and swallowed.
 */
class WebJobProducer implements JobProducerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var QueueInterface */
    protected $queue;

    public function __construct(QueueInterface $queue, LoggerInterface $logger = null)
    {
        $this->queue = $queue;
        $this->setLogger($logger ?: new NullLogger());
    }

    public function push($tube, $data, $priority = null, $delay = null, $ttr = null)
    {
        $context = array(
            'tube' => $tube,
            'data' => $data,
        );
        try {
            return $this->queue->push($tube, $data, $priority, $delay, $ttr);
        } catch (\Exception $e) {
            $this->logger->error('Error pushing job to queue', $context);
        }

        return -1;
    }
}
