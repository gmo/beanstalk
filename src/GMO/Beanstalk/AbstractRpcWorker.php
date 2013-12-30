<?php
namespace GMO\Beanstalk;

use GMO\Common\Collection;
use GMO\Common\String;
use Psr\Log\LoggerInterface;

/**
 * Abstracts the repetitive worker tasks for Remote Procedure Call (RPC).
 * RPC involves doing work and sending the result back to the producer.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 *
 * @package GMO\Beanstalk
 * 
 * @since 1.3.0
 */
abstract class AbstractRpcWorker extends AbstractWorker {
		
	/**
	 * Get the replyTo when pre processing job data
	 * @param \Pheanstalk_Job $job
	 */
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
	 * @since 1.3.0
	 */
	protected function postProcess( $job ) {
		if(empty($this->replyTo)) {
			return;
		}
		
		if(!$this->isTubeWatched( $tube )) {
			return;
		}
		
		$result = array(
			'result' => $this->result);
		$data = json_encode( $result );
		$this->pheanstalk->useTube( $this->replyTo )->put( $data );
	}
	
	/**
	 * Returns true if the tube has at least one watcher
	 * @param bool $tube
	 */
	private function isTubeWatched( $tube ) {
		// TODO: Implement this function
		return true;
	}
	
	const RPC_REPLY_TO_FIELD = 'rpcReplyTo';
	
	/**
	 * The tube to send the reply back to
	 * @var string
	 */
	private $replyTo;
}