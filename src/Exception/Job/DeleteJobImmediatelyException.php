<?php
namespace GMO\Beanstalk\Exception\Job;

use GMO\Beanstalk\Job\JobError\Action\DeleteJobAction;
use GMO\Beanstalk\Job\JobError\Delay\NoJobDelay;
use GMO\Beanstalk\Job\JobError\Retry\NoJobRetry;

class DeleteJobImmediatelyException extends JobException {

	public function __construct(\Exception $exception) {
		parent::__construct($exception, new NoJobDelay(), new NoJobRetry(), new DeleteJobAction());
	}
}
