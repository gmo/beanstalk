<?php
namespace GMO\Beanstalk\Exception\Job;

use GMO\Beanstalk\Exception\ExceptionInterface;
use GMO\Beanstalk\Job\JobError\Action\BuryJobAction;
use GMO\Beanstalk\Job\JobError\JobErrorInterface;
use GMO\Common\Exception\NotSerializableException;

class NotSerializableJobException extends NotSerializableException implements JobErrorInterface, ExceptionInterface {

	public function getMaxRetries() { return 0; }

	public function getDelay($numRetries) { return 0; }

	public function getActionToTake() { return new BuryJobAction(); }
}
