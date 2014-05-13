<?php
namespace GMO\Beanstalk;

use GMO\Beanstalk\Exception\RpcTimeoutException;
use GMO\Beanstalk\Exception\RpcInvalidResponseException;

class RpcQueue extends Queue {
	
	public function runRpc( $tube, $data, $timeout = 30, $jsonEncode = true ) {
		$replyToTube = static::makeRpcUid();
		$data[static::RPC_REPLY_TO_FIELD] = $replyToTube;
		
		$this->push($tube, $data, $jsonEncode);
		
		try {
			$job = $this->pheanstalk->reserveFromTube($replyToTube, $timeout);
			$this->stopWatchingRpcTubes();
			
			if(empty($job)) {
				throw new RpcTimeoutException('Timed Out'); 
			}
			
			$this->pheanstalk->delete($job);
			
			$result = json_decode( $job->getData(), true );
			
			if(!is_array($result) || !isset($result['result'])) {
				throw new RpcInvalidResponseException('Result not sent');
			}
		} catch ( \Pheanstalk_Exception_ServerException $e ) {
			throw new RpcInvalidResponseException('Queue Server Exception: ' . $e->getMessage());
		}
		
		return $result['result'];
	}
	
	private static function makeRpcUid() {
		// TODO: Consider replacing this with a UUID generator
		return bin2hex(openssl_random_pseudo_bytes(90));
	}
	
	private function stopWatchingRpcTubes() {
		$this->pheanstalk->watchOnly('default');
	}

	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';
	
}