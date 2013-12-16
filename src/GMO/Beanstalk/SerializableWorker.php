<?php
namespace GMO\Beanstalk;

use GMO\Beanstalk\Exception\NotSerializableJobException;
use GMO\Common\ISerializable;

/**
 * SerializableWorker is a worker that takes jobs that have been serialized with GMO\Common\ISerializable
 * @package GMO\Beanstalk
 * @since 1.3.0
 */
abstract class SerializableWorker extends AbstractWorker {

	/**
	 *
	 * @param ISerializable $obj
	 * @return void
	 */
	abstract protected function processSerializableObject($obj);

	/**
	 * Gets the class name of the current job
	 * @return string
	 */
	protected function getClassNameOfJob() {
		return $this->className;
	}

	/**
	 * Unserialize array into ISerializable object and call processSerializableObject()
	 * @param array $params json decoded trimmed parameters
	 * @throws Exception\NotSerializableJobException
	 * @return void
	 */
	protected function process($params) {
		if (!isset($params["class"])) {
			throw new NotSerializableJobException('Job params are missing the "class" attribute');
		} elseif (!is_subclass_of($params["class"], 'GMO\Common\ISerializable')) {
			throw new NotSerializableJobException($params["class"] . ' does not implement GMO\Common\ISerializable');
		}

		/** @var \GMO\Common\ISerializable $cls */
		$this->className = $cls = $params["class"];

		$obj = $cls::fromArray($params);
		$this->processSerializableObject($obj);
	}

	private $className;
}
