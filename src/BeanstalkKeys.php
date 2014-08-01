<?php
namespace GMO\Beanstalk;

final class BeanstalkKeys {

	const HOST = 'beanstalk.host';
	const PORT = 'beanstalk.port';
	const WORKER_DIRECTORY = 'worker_manager.directory';
	const QUEUE_LOGGER = 'queue.logger';
	const WORKER_MANAGER_LOGGER = 'worker_manager.logger';
	const QUEUE = 'queue';
	const QUEUE_RPC = 'queue.rpc';
	const WORKER_MANAGER = 'worker_manager';

	private function __construct() { }
}
