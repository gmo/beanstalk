<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Job\JobProducerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * WebJobProducer is designed for producing jobs within a web request context.
 * All exceptions will be logged and swallowed.
 */
class WebJobProducer implements JobProducerInterface, LoggerAwareInterface {

	public function push($tube, $data, $priority = null, $delay = null, $ttr = null) {
		$context = array(
			'tube' => $tube,
			'data' => $data,
		);
		try {
			return $this->queue->push($tube, $data, $priority, $delay, $ttr);
		} catch (\Exception $e) {
			$this->log->error('Error pushing job to queue', $context);
		}
		return -1;
	}

	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
	}

	public function __construct(QueueInterface $queue, LoggerInterface $logger = null) {
		$this->queue = $queue;
		$this->setLogger($logger ?: new NullLogger());
	}

	/** @var LoggerInterface */
	protected $log;
	/** @var QueueInterface */
	protected $queue;
}
