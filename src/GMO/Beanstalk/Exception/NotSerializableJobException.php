<?php
namespace GMO\Beanstalk\Exception;

use GMO\Common\Exception\NotSerializableException;

/**
 * Class NotSerializableJobException
 * @package GMO\Beanstalk\Exception
 * @since 1.3.0
 */
class NotSerializableJobException extends NotSerializableException implements IJobAwareException {

	/**
	 * Yes, delete the job
	 * {@see \GMO\Beanstalk\IJobAwareException::deleteAfter()}
	 * @return bool
	 */
	public function shouldDelete() { return true; }

	/**
	 * Delete the job immediately because
	 * nothing will change to make it serializable
	 * @return int
	 */
	public function deleteAfter() { return 0; }
}