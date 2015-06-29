<?php
namespace GMO\Beanstalk\Job;

use GMO\Common\Collections\ArrayCollection;

class JobCollection extends ArrayCollection {

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function get($key, $default = null) {
		return parent::get($key, $default) ?: new NullJob();
	}

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function first() {
		return parent::first() ?: new NullJob();
	}

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function last() {
		return parent::last() ?: new NullJob();
	}

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function offsetGet($offset) {
		return parent::offsetGet($offset);
	}

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function remove($key) {
		return parent::remove($key) ?: new NullJob();
	}

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function removeFirst() {
		return parent::removeFirst();
	}

	/**
	 * @inheritdoc
	 * @return Job
	 */
	public function removeLast() {
		return parent::removeLast();
	}
}
