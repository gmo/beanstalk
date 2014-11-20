<?php
namespace GMO\Beanstalk;

final class BeanstalkKeys {

	const HOST = 'beanstalk.host';
	const PORT = 'beanstalk.port';
	const WORKER_DIRECTORY = 'beanstalk.worker_manager.directory';
	const QUEUE_LOGGER = 'beanstalk.queue.logger';
	const QUEUE = 'beanstalk.queue';
	const WEB_JOB_PRODUCER = 'beanstalk.queue.web_job_producer';
	const WORKER_MANAGER_LOGGER = 'beanstalk.worker_manager.logger';
	const WORKER_MANAGER = 'beanstalk.worker_manager';

	private function __construct() { }
}
