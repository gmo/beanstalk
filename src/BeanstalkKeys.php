<?php
namespace GMO\Beanstalk;

final class BeanstalkKeys {

	const HOST = 'beanstalk.host';
	const PORT = 'beanstalk.port';
	const WORKER_DIRECTORY = 'worker_manager.directory';
	const QUEUE_LOGGER = 'queue.logger';
	const QUEUE = 'queue';
	const WEB_JOB_PRODUCER = 'queue.web_job_producer';
	const WORKER_MANAGER_LOGGER = 'worker_manager.logger';
	const WORKER_MANAGER = 'worker_manager';

	private function __construct() { }
}
