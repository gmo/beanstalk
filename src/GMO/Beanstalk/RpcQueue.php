<?php
namespace GMO\Beanstalk;

class RpcQueue extends Queue {
	
	public function runRpc( $tube, $data, $timeout = 30, $jsonEncode = true ) {
		// TODO: Add try-catch blocks to this, test
		$replyToTube = static::makeRpcUid();
		$data[static::RPC_REPLY_TO_FIELD] = $replyToTube;
		
		$this->push($tube, $data, $jsonEncode);
		$job = $this->pheanstalk->reserveFromTube($replyToTube, $timeout);
		
		if(empty($job)) {
			throw new RpcTimeoutException('Timed Out'); 
		}
		
		$result = json_decode( $job->getData(), true );
		
		if(!is_array($result) || !isset($result['result'])) {
			throw new RpcInvalidResponseException('Result not sent');
		}
		
		return $result['result'];
	}
	
	private static function makeRpcUid() {
		// This ought to be good enough in the absence of a good UUID generator
		return bin2hex(openssl_random_pseudo_bytes(90));
	}

	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';
	
}

class RpcTimeoutException extends \Exception {}
class RpcInvalidResponseException extends \Exception {}