<?php
namespace GMO\Beanstalk\Helper;

class MapIteratorWrapper implements \Iterator {

	public function current() {
		return call_user_func($this->func, $this->iterator->current());
	}

	public function next() {
		$this->iterator->next();
	}

	public function key() {
		return $this->iterator->key();
	}

	public function valid() {
		return $this->iterator->valid();
	}

	public function rewind() {
		$this->iterator->rewind();
	}

	/**
	 * @param \Iterator $iterator
	 * @param callable  $func
	 */
	public function __construct(\Iterator $iterator, $func) {
		$this->iterator = $iterator;
		$this->func = $func;
	}

	private $iterator;
	private $func;
}
