<?php
namespace GMO\Beanstalk\Exception\Job;

use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\Delay\NoJobDelay;
use GMO\Beanstalk\Job\JobError\Retry\NoJobRetry;

class BuryJobImmediatelyException extends JobException {

	public function __construct(\Exception $exception) {
		parent::__construct($exception, new NoJobDelay(), new NoJobRetry(), new BuryJobAction());
	}
}
