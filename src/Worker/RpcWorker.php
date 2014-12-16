<?php
namespace GMO\Beanstalk\Worker;

use GMO\Beanstalk\Exception\RpcInvalidResponseException;
use GMO\Beanstalk\Exception\RpcTimeoutException;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Runner\RpcRunner;
use GMO\Beanstalk\Tube\TubeControlInterface;

abstract class RpcWorker extends AbstractWorker {

	public static function getRunner() { return new RpcRunner(); }

	public static function getTimeToRun() { return 30; }

	public static function runRpc(TubeControlInterface $queue, $data, $priority = null) {
		$replyToTube = static::makeReplyTube();
		static::pushData($queue, array(
			RpcRunner::RPC_REPLY_TO_FIELD => $replyToTube,
			'data' => $data
		), $priority);

		$job = $queue->reserve($replyToTube, static::getTimeToRun(), true);
		if ($job instanceof NullJob) {
			throw new RpcTimeoutException();
		}
		$job->delete();

		$data = $job->getData();
		if (!$result = $data['result']) {
			throw new RpcInvalidResponseException('Result not sent');
		}
		return $result;
	}

	private static function makeReplyTube() {
		return substr(static::getTubeName() . '-' . static::makeRpcUid(), 0, 190);
	}

	private static function makeRpcUid() {
		// TODO: Consider replacing this with a UUID generator
		return bin2hex(openssl_random_pseudo_bytes(90));
	}
}
