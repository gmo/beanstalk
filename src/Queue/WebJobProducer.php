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
		if (!$this->queue) {
			$this->log->error('Queue not initialized, cannot push job', $context);
			return -1;
		}
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

	public function __construct($host = 'localhost', $port = 11300, LoggerInterface $logger = null) {
		$this->setLogger($logger ?: new NullLogger());
		try {
			$this->queue = new Queue($host, $port, $logger);
		} catch (\Exception $e) {
			$this->queue = null;
			$this->log->error('Error creating Queue', array(
				'exception' => $e,
			));
		}
	}

	/** @var LoggerInterface */
	protected $log;
	/** @var QueueInterface */
	protected $queue;
}
