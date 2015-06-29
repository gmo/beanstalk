<?php
namespace GMO\Beanstalk\Log;

use GMO\Beanstalk\Job\Job;

class JobProcessor {

	public function setCurrentJob(Job $job = null) {
		$this->currentJob = $job;
	}

	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}

	public function __invoke(array $record) {
		if (!$this->currentJob || $this->currentJob->getId() === -1) {
			return $record;
		}

		$params = array(
			'id' => $this->currentJob->getId(),
			'data' => $this->currentJob->getData(),
			'result' => $this->currentJob->getResult(),
		);
		$record['extra'][$this->prefix . 'job'] = $params;
		return $record;
	}

	/** @var Job|null */
	protected $currentJob;
	/** @var string */
	protected $prefix = '';
}
