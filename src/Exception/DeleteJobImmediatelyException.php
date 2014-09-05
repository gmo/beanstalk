<?php
namespace GMO\Beanstalk\Exception;

class DeleteJobImmediatelyException extends \Exception implements JobAwareExceptionInterface {

	public function shouldDelete() { return true; }

	public function deleteAfter() { return 0; }
}
