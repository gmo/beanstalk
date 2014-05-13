<?php
namespace GMO\Beanstalk\Exception;

/**
 * This interface allows workers to better handle exceptions.
 * When an exception implementing this interface is thrown,
 * the worker will catch it and check the shouldDelete method.
 * If shouldDelete returns true the worker will delete the job
 * after the number of failures specified is reached.
 * Else, the worker buries the job and continues.
 *
 * @package GMO\Beanstalk
 *
 * @since 1.3.0
 */
interface IJobAwareException {

	/**
	 * Check whether the job should be deleted
	 * after number of failures specified in
	 * {@see \GMO\Beanstalk\IJobAwareException::deleteAfter()}
	 * @return bool
	 */
	public function shouldDelete();

	/**
	 * How many times the job should be retried
	 * before deletion (given that shouldDelete is true)
	 * @return int
	 */
	public function deleteAfter();
}