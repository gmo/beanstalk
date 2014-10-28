<?php
namespace GMO\Beanstalk\Queue;

/**
 * Implementation of {@see QueueAwareInterface}
 * @see QueueAwareInterface
 */
trait QueueAwareTrait {

	/** @var QueueInterface */
	protected $queue;

	/**
	 * Sets the queue instance
	 * @param QueueInterface $queue
	 */
	public function setQueue(QueueInterface $queue) {
		$this->queue = $queue;
	}
}
