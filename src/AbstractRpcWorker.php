<?php
namespace GMO\Beanstalk;

/**
 * Abstracts the repetitive worker tasks for Remote Procedure Call (RPC).
 * RPC involves doing work and sending the result back to the producer.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 *
 * @package GMO\Beanstalk
 * 
 * @since 1.5.0
 */
abstract class AbstractRpcWorker extends AbstractWorker {

	/** {@inheritDoc} */
	public function run( $host, $port ) {
		$this->queue = Queue::getInstance($this->getLogger(), $host, $port );
		parent::run( $host, $port );
	}
	
	/** {@inheritDoc} */
	protected function preProcess( $job ) {
		$result = parent::preProcess( $job );
		if(isset($result[static::RPC_REPLY_TO_FIELD]) && !empty($result[static::RPC_REPLY_TO_FIELD])) {
			$this->replyTo = $result[static::RPC_REPLY_TO_FIELD];
		}
		return $result;
	}
	
	/**
	 * In post-processing, send the results back to the producer
	 * @param \Pheanstalk_Job $job
	 * @since 1.5.0
	 */
	protected function postProcess( $job ) {
		if(empty($this->replyTo)) {
			return;
		}
		
		if(!$this->isTubeWatched( $this->replyTo )) {
			$this->log->debug("No one is listening, not pushing to return queue");
			return;
		}
		
		$data = array('result' => $this->result);
		$this->queue->push( $this->replyTo, $data );
	}
	
	/**
	 * Returns true if the tube has at least one watcher
	 * @param string $tube
	 * @return bool
	 */
	private function isTubeWatched( $tube ) {
		
		if(!$this->doesTubeExist( $tube )) {
			return false;
		}
		
		$stats = $this->queue->getStats( $tube );
		if( !isset($stats['current-watching']) ) {
			return false;
		}

		return intval($stats['current-watching']) > 0;
	}
	
	/**
	 * Returns true if the tube exists
	 * @param string $tube
	 * @return bool
	 */
	private function doesTubeExist( $tube ) {
		$maxRetry = 3;
		$retry = 0;
		do {
			if($retry > 0) {
				usleep(100000);
			}
			
			$tubeList = $this->queue->listTubes();
				
			if(array_search( $tube, $tubeList ) !== false) {
				return true;
			}
			$retry++;
		} while ($retry < $maxRetry);
		
		return false;		
	}
	
	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';
	
	/**
	 * The tube to send the reply back to
	 * @var string
	 */
	private $replyTo;

	/** @var Queue */
	private $queue;
}