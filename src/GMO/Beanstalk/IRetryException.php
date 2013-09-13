<?php
namespace GMO\Beanstalk;

/**
 * This interface allows workers to better handle exceptions.
 * When an exception implementing this interface is thrown,
 * the worker will catch it and check the shouldRetry method.
 * If shouldRetry returns true the worker will try to process
 * the job again (up to 3 times).
 * Else, the worker deletes the job.
 * If the exception does not implement this interface
 * the worker will try again.
 * @package GMO\Beanstalk
 */
interface IRetryException {

	/**
	 * Check whether the job should be retried, or deleted.
	 * @return bool
	 */
	public function shouldRetry();
}