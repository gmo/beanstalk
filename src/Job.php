<?php
namespace GMO\Beanstalk;

class Job extends \Pheanstalk\Job implements \ArrayAccess, \IteratorAggregate {

	private $parsedData = array();
	private $result;

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
}
