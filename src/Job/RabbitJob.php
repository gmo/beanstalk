<?php

namespace GMO\Beanstalk\Job;

use PhpAmqpLib\Message\AMQPMessage;

class RabbitJob extends Job {

	/** @var AmqpMessage */
	protected $message;

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
}
