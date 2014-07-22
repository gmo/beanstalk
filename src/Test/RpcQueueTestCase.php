<?php
namespace GMO\Beanstalk\Test;

use GMO\Beanstalk\RpcQueue;

/**
 * Class QueueTestCase
 * @package GMO\Beanstalk\Test
 * @since 1.7.0
 */
class RpcQueueTestCase extends QueueTestCase {

	/** @var RpcQueue */
	protected static $queue;

	protected static function createQueueClass() {
		return new RpcQueue(static::getHost(), static::getPort(), static::getLogger());
	}

}
