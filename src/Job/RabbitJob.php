<?php

namespace GMO\Beanstalk\Job;

use PhpAmqpLib\Message\AMQPMessage;

class RabbitJob extends Job {

	/** @var AmqpMessage */
	protected $message;
	protected $state;

	/**
	 * @param AMQPMessage         $message
	 * @param string              $data
	 * @param string              $state
	 * @param JobControlInterface $queue
	 */
	public function __construct(AMQPMessage $message, $data, $state, JobControlInterface $queue) {
		$this->message = $message;
		$this->state = $state;
		parent::__construct($message->get('correlation_id'), $data, $queue);
	}

	/**
	 * @return AMQPMessage
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @param AMQPMessage $message
	 */
	public function setMessage(AMQPMessage $message) {
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getDeliveryTag() {
		return $this->message->get('delivery_tag');
	}

	/**
	 * @return string
	 */
	public function getTubeName() {
		return $this->message->get('routing_key');
	}

	public function setPriority($priority) {
		$this->message->set('priority', $priority);
	}

	public function setState($state) {
		$this->state = $state;
	}

	public function getState() {
		return $this->state;
	}
}
