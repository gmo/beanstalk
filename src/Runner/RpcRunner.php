<?php
namespace Runner;

use GMO\Beanstalk\Job;
use GMO\Beanstalk\Runner\BaseRunner;
use GMO\Common\Collection;

/**
 * Abstracts the repetitive worker tasks for Remote Procedure Call (RPC).
 * RPC involves doing work and sending the result back to the producer.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 */
class RpcRunner extends BaseRunner {

	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';
	const CLS = 'GMO\Beanstalk\Runner\RpcRunner';

	public function preProcessJob(Job $job) {
		parent::preProcessJob($job);
		$this->replyTo = Collection::get($job->getData(), static::RPC_REPLY_TO_FIELD);
	}

	public function postProcessJob(Job $job) {
		parent::postProcessJob($job);

		if (!$this->replyTo) {
			return;
		}

		if (!$this->isTubeWatched($this->replyTo)) {
			$this->log->debug("No one is listening, not pushing to return queue");
			return;
		}

		$data = array( 'result' => $job->getResult() );
		$this->queue->push($this->replyTo, $data);
	}

	/**
	 * Returns true if the tube has at least one watcher
	 * @param string $tube
	 * @return bool
	 */
	private function isTubeWatched($tube) {

		if (!$this->doesTubeExist($tube)) {
			return false;
		}

		$stats = $this->queue->stats($tube);
		if (!isset($stats['current-watching'])) {
			return false;
		}

		return intval($stats['current-watching']) > 0;
	}

	/**
	 * Returns true if the tube exists
	 * @param string $tube
	 * @return bool
	 */
	private function doesTubeExist($tube) {
		$maxRetry = 3;
		$retry = 0;
		do {
			if ($retry > 0) {
				usleep(100000);
			}

			$tubeList = $this->queue->listTubes();

			if (array_search($tube, $tubeList) !== false) {
				return true;
			}
			$retry++;
		} while ($retry < $maxRetry);

		return false;
	}

	/** @var string The tube to send the reply back to */
	private $replyTo;
}
