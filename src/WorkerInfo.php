<?php
namespace GMO\Beanstalk;

use GMO\Common\String;

class WorkerInfo {

	/** @return string Fully qualified class name */
	public function getFullyQualifiedName() {
		return $this->fullyQualifiedName;
	}

	/** @return string class name without namespace */
	public function getName() {
		if (!$this->name) {
			$this->name = String::className($this->fullyQualifiedName);
		}
		return $this->name;
	}

	/** @return int number of workers currently running */
	public function getNumRunning() {
		return count($this->pids);
	}

	/** @return int total number of workers */
	public function getTotal() {
		return $this->getInstance()->getNumberOfWorkers();
	}

	public function getReflectionClass() {
		if (!$this->refCls) {
			$this->refCls = new \ReflectionClass($this->fullyQualifiedName);
		}
		return $this->refCls;
	}

	/** @return AbstractWorker */
	public function getInstance() {
		if (!$this->instance) {
			$this->instance = $this->getReflectionClass()->newInstance();
		}
		return $this->instance;
	}

	/** @return int[] */
	public function getPids() {
		return $this->pids;
	}

	/**
	 * @param int $pid
	 */
	public function addPid($pid) {
		$this->pids[] = (int) $pid;
	}

	/**
	 * @param int $pid
	 */
	public function removePid($pid) {
		if(($key = array_search($pid, $this->pids)) !== false) {
			unset($this->pids[$key]);
		}
	}

	public function __construct($fullyQualifiedName) {
		$this->fullyQualifiedName = $fullyQualifiedName;
	}

	private $fullyQualifiedName;
	private $name;
	private $refCls;
	private $instance;
	private $pids = array();
}
