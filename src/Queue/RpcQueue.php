<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Exception\RpcInvalidResponseException;
use GMO\Beanstalk\Exception\RpcTimeoutException;
use Pheanstalk\Exception\ServerException;

class RpcQueue extends Queue {

	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';

	public function runRpc($tube, $data, $timeout = 30, $jsonEncode = true) {
		$replyToTube = static::makeRpcUid();
		$data[static::RPC_REPLY_TO_FIELD] = $replyToTube;

		$this->push($tube, $data, $jsonEncode);

		try {
			$job = $this->pheanstalk->reserveFromTube($replyToTube, $timeout);
			$this->stopWatchingRpcTubes();

			if (empty($job)) {
				throw new RpcTimeoutException('Timed Out');
			}

			$this->deleteJob($job);

			$result = json_decode($job->getData(), true);

			if (!is_array($result) || !isset($result['result'])) {
				throw new RpcInvalidResponseException('Result not sent');
			}
		} catch (ServerException $e) {
			throw new RpcInvalidResponseException('Queue Server Exception: ' . $e->getMessage());
		}

		return $result['result'];
	}

	private function stopWatchingRpcTubes() {
		$this->pheanstalk->watchOnly('default');
	}

	private static function makeRpcUid() {
		// TODO: Consider replacing this with a UUID generator
		return bin2hex(openssl_random_pseudo_bytes(90));
	}
}
