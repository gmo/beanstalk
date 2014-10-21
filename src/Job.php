<?php
namespace GMO\Beanstalk;

use GMO\Beanstalk\Queue\JobControlInterface;

class Job extends \Pheanstalk\Job implements \ArrayAccess, \IteratorAggregate {

	protected $parsedData = array();
	protected $result;
	/** @var JobControlInterface */
	protected $queue;

	public function __construct($id, $data, JobControlInterface $queue) {
		$this->queue = $queue;
		parent::__construct($id, $data);
	}

	public function setParsedData($data) {
		$this->parsedData = $data;
	}

	/**
	 * @return string|mixed|\GMO\Common\Collections\ArrayCollection
	 */
	public function getData() {
		return $this->parsedData ?: parent::getData();
	}

	public function setResult($result) {
		$this->result = $result;
	}

	public function getResult() {
		return $this->result;
	}

	//region Job Control Methods
	public function release($delay = null, $priority = null) {
		$this->queue->release($this, $priority, $delay);
	}

	public function bury() {
		$this->queue->bury($this);
	}

	public function delete() {
		$this->queue->delete($this);
	}

	public function kick() {
		$this->queue->kickJob($this);
	}

	public function touch() {
		$this->queue->touch($this);
	}

	public function stats() {
		return $this->queue->statsJob($this);
	}
	//endregion

	//region Array and Iterator Methods
	/** @inheritdoc */
	public function offsetExists($offset) {
		return isset($this->parsedData[$offset]);
	}

	/** @inheritdoc */
	public function offsetGet($offset) {
		return $this->parsedData[$offset];
	}

	/** @inheritdoc */
	public function offsetSet($offset, $value) {
		$this->parsedData[$offset] = $value;
	}

	/** @inheritdoc */
	public function offsetUnset($offset) {
		unset($this->parsedData[$offset]);
	}

	/** @inheritdoc */
	public function getIterator() {
		return new \ArrayIterator($this->parsedData);
	}
	//endregion
}
