<?php
namespace GMO\Beanstalk\Job;

/**
 * A Job that has been inspected from the management plugin.
 * This is basically a read-only version of a RabbitJob.
 */
class RabbitManagementJob extends RabbitJob {

	public function release($delay = null, $priority = null) {
		throw new \Exception('Management job cannot change state');
	}

	public function bury() {
		throw new \Exception('Management job cannot change state');
	}

	public function delete() {
		throw new \Exception('Management job cannot change state');
	}

	public function kick() {
		throw new \Exception('Management job cannot change state');
	}
}
