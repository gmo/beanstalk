<?php
namespace GMO\Beanstalk;

class RpcQueue extends Queue {
	
	public function runRpc( $tube, $data, $timeout = 30, $jsonEncode = true ) {
		$replyToTube = static::makeRpcUid();
		$data[static::RPC_REPLY_TO_FIELD] = $replyToTube;
		
		$this->push($tube, $data, $jsonEncode);
		
		try {
			$job = $this->pheanstalk->reserveFromTube($replyToTube, $timeout);
			
			if(empty($job)) {
				throw new RpcTimeoutException('Timed Out'); 
			}
			
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

	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';
	
}

class RpcTimeoutException extends \Exception {}
class RpcInvalidResponseException extends \Exception {}