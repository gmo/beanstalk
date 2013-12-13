<?php
namespace GMO\Beanstalk;

use Psr\Log\LoggerInterface;

/**
 * Abstracts the repetitive worker tasks, such as getting jobs and validating parameters.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 *
 * @package GMO\Beanstalk
 *
 * @since 1.2.0 Catching IJobAwareException
 * @since 1.1.0 Added preProcess method
 * @since 1.0.0
 */
abstract class AbstractWorker {

	/**
	 * Return worker name. By default it is the class name.
	 * @return string
	 */
	public static function getTubeName() {
		$classWithNamespace = get_called_class();
		$parts = explode( "\\", $classWithNamespace );
		$class = $parts[count( $parts ) - 1];
		return $class;
	}

	/**
	 * Return number of workers to spawn. By default it is one.
	 * @return int
	 */
	public static function getNumberOfWorkers() { return 1; }

	/**
	 * Only process one job. Used for testing.
	 * @internal
	 */
	public function setToRunOnce() {
		$this->keepRunning = false;
	}

	/**
	 * Return an array of parameters required for job to continue.
	 * By default it is empty.
	 * @return array
	 */
	protected function getRequiredParams() { return array(); }

	/**
	 * Setup worker to run. Called only one time.
	 * Note: Pheanstalk does not need to be setup.
	 * @return void
	 */
	protected function setup() { }

	/**
	 * Process each job
	 * @param array $params json decoded trimmed parameters
	 * @return void
	 */
	protected abstract function process( $params );

	/**
	 * Returns a logger instance for worker
	 * @return LoggerInterface
	 */
	protected abstract function getLogger();

	/**
	 * Do not call this method directly.
	 * Use {@see \GMO\Beanstalk\WorkerManager} to start a worker.
	 *
	 * @internal
	 * @access private
	 * @param string $host
	 * @param int $port
	 * @throws \Exception
	 */
	public function run( $host, $port ) {
		$this->log->info( "Beanstalkd: Running worker: " . $this->getTubeName() );

		try {
			$this->pheanstalk = new \Pheanstalk_Pheanstalk($host, $port);
			$this->setup();
		} catch ( \Exception $e ) {
			$this->log->critical(
				"Beanstalkd: An error occurred in setting up the worker: " . $this->getTubeName(),
				array( "exception" => $e )
			);
			throw $e;
		}

		do {
			$this->log->debug( "Beanstalkd: Getting next job from: " . $this->getTubeName() );
			$this->getJob();

			$isValid = $this->validateParams( $this->params );
			if ( !$isValid ) {
				$this->log->error(
					"Beanstalkd: \"{$this->getTubeName()}\" current job missing required params is being deleted!"
				);
				$this->deleteJob();
				continue;
			}
			try {
				$this->log->debug( "Beanstalkd: Processing the job from: " . $this->getTubeName() );
				$this->process( $this->params );
				$this->deleteJob();
			} catch ( \Exception $e ) { //TODO: Remove exceptions in 2.0.0
				if ($e instanceof IJobAwareException || $e instanceof Exception\IJobAwareException) {
					$numErrors = $this->handleError($e);
					if ( $e->shouldDelete() && $numErrors >= $e->deleteAfter() ) {
						$this->handleFatal($e);
					}
				} elseif ($e instanceof IRetryException) {
					if ( $e->shouldRetry() ) {
						$this->handleRetry( $e );
					} else {
						$this->handleFatal( $e );
					}
				} else {
					$this->handleError( $e );
				}
			}
		} while ( $this->keepRunning );
	}

	/**
	 * Gets the data from the job and
	 * returns an associative array of job data.
	 * By default data is json decoded and values are trimmed.
	 * @param \Pheanstalk_Job $job
	 * @return array job params
	 * @since 1.1.0
	 */
	protected function preProcess( $job ) {
		# Get params and trim values
		$params = json_decode( $job->getData(), true );
		foreach ( $params as $key => $value ) {
			$params[$key] = trim( $value );
		}

		return $params;
	}

	/**
	 * Pheanstalk is setup here.
	 * Do NOT override this; instead override setup.
	 */
	public function __construct() {
		$this->log = $this->getLogger();
	}

	private function getJob() {
		# Get job and bury
		$this->currentJob = $this->pheanstalk->watchOnly( $this->getTubeName() )->reserve();
		$this->pheanstalk->bury( $this->currentJob );

		$this->params = $this->preProcess($this->currentJob);
	}

	/**
	 * Validates current params against getRequiredParams()
	 * @param array $params
	 * @return bool
	 */
	private function validateParams( $params ) {
		foreach ( $this->getRequiredParams() as $reqParam ) {
			if ( !array_key_exists( $reqParam, $params ) ) {
				$this->log->error(
					"Beanstalkd: \"{$this->getTubeName()}\" current job is missing required param: \"$reqParam\""
				);

				return false;
			}
		}

		return true;
	}

	/**
	 * Log failure and return number of times it has failed.
	 * @param \Exception $e
	 * @return int Number of failures
	 */
	private function handleError( $e ) {
		$id = $this->currentJob->getId();
		# Increment job id errors
		if ( !isset($this->jobErrors[$id]) ) {
			$this->jobErrors[$id] = 0;
		}
		$numErrors = ++$this->jobErrors[$id];

		$this->log->warning(
			"Beanstalkd: Job: $id failed $numErrors times.",
			array(
			   "params" => $this->params,
			   "exception" => $e
			)
		);

		return $numErrors;
	}

	/**
	 * Check if job has failed three times,
	 * in which case it is deleted.
	 * @param $e
	 * @deprecated Use {@see \GMO\Beanstalk\AbstractWorker::handleError()} instead
	 * @todo Remove in 2.0.0
	 */
	private function handleRetry( $e ) {
		$id = $this->currentJob->getId();
		# Increment job id errors
		if ( !isset($this->jobErrors[$id]) ) {
			$this->jobErrors[$id] = 0;
		}
		$numErrors = ++$this->jobErrors[$id];

		$this->log->warning(
			"Beanstalkd: Job: $id failed $numErrors times.",
			array(
			     "params" => $this->params,
			     "exception" => $e
			)
		);

		if ( $numErrors > 2 ) {
			$this->log->error("Beanstalkd: Job: $id failed 3 times...job will be deleted.");
			$this->deleteJob();
		}
	}

	/**
	 * Job should not be retried and is deleted.
	 * @param $e
	 */
	private function handleFatal( $e ) {
		$id = $this->currentJob->getId();

		$this->log->warning(
			"Beanstalkd: Not retrying job: $id fatal error...job will be deleted.",
			array(
			     "params" => $this->params,
			     "exception" => $e
			)
		);
		$this->deleteJob();
	}

	private function deleteJob() {
		$this->log->debug( "Beanstalkd: Deleting the current job from: " . $this->getTubeName() );
		try {
			$this->pheanstalk->delete( $this->currentJob );
		} catch ( \Pheanstalk_Exception_ServerException $e ) {
			$this->log->warning( "Error deleting job", array("exception" => $e) );
		}
	}

	/** @var \Pheanstalk_Job Current job being processed */
	protected $currentJob;

	/**
	 * Associative array of job ids with the number
	 * of times they've thrown an exception
	 * @var array
	 */
	protected $jobErrors = array();

	/** @var LoggerInterface Worker logger */
	protected $log;

	/** @var array Current Job JSON decoded array */
	private $params;

	/** @var \Pheanstalk_Pheanstalk */
	private $pheanstalk;

	/** @var bool Boolean for running loop */
	private $keepRunning = true;
}