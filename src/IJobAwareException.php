<?php
namespace GMO\Beanstalk;

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
 * @since 1.2.0
 * @deprecated Use {@see \GMO\Beanstalk\Exception\IJobAwareException} instead
 * @todo Remove in version 2.0.0
 */
interface IJobAwareException extends Exception\IJobAwareException { }