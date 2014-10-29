<?php
namespace GMO\Beanstalk\Job;

use GMO\Common\Collections\ArrayCollection;

class Job extends \Pheanstalk\Job implements \ArrayAccess, \IteratorAggregate {

	/**
	 * Pheanstalk\Job::data is ignored because it's private and we allow write access
	 * @var ArrayCollection|string
	 */
	protected $jobData = array();
	protected $result;
	/** @var JobControlInterface */
	protected $queue;
	protected $handled = false;

	public function __construct($id, $data, JobControlInterface $queue) {
		$this->queue = $queue;
		$this->jobData = is_array($data) ? new ArrayCollection($data) : $data;
		parent::__construct($id, $this->jobData);
	}

	public function setData($data) {
		$this->jobData = $data;
	}

	/**
	 * @return string|mixed|\GMO\Common\Collections\ArrayCollection
	 */
	public function getData() {
		return $this->jobData;
	}

	public function setResult($result) {
		$this->result = $result;
	}

	public function getResult() {
		return $this->result;
	}

	/**
	 * Returns whether the job has been handled (released, buried, deleted)
	 * @return bool
	 */
	public function isHandled() {
		return $this->handled;
	}

	//region Job Control Methods
	/**
	 * @param int $delay    Seconds to wait before job becomes ready
	 * @param int $priority From 0 (most urgent) to 4294967295 (least urgent)
	 */
	public function release($delay = null, $priority = null) {
		$this->handled = true;
		$this->queue->release($this, $priority, $delay);
	}

	public function bury() {
		$this->handled = true;
		$this->queue->bury($this);
	}

	public function delete() {
		$this->handled = true;
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
		return isset($this->jobData[$offset]);
	}

	/** @inheritdoc */
	public function offsetGet($offset) {
		return $this->jobData[$offset];
	}

	/** @inheritdoc */
	public function offsetSet($offset, $value) {
		$this->jobData[$offset] = $value;
	}

	/** @inheritdoc */
	public function offsetUnset($offset) {
		unset($this->jobData[$offset]);
	}

	/** @inheritdoc */
	public function getIterator() {
		return new \ArrayIterator($this->jobData);
	}
	//endregion
}