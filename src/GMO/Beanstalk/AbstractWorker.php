<?php
namespace GMO\Beanstalk;

use GMO\Common\Collection;
use GMO\Common\String;
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
		return String::splitLast(get_called_class(), "\\");
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
	 * @return void|mixed
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
		$this->log->info( "Running worker: " . $this->getTubeName() );

		pcntl_signal(SIGTERM, array($this, 'signalHandler'));

		try {
			$this->pheanstalk = new \Pheanstalk_Pheanstalk($host, $port);
			$this->setup();
		} catch ( \Exception $e ) {
			$this->log->critical(
				"An error occurred when setting up the worker",
				array( "exception" => $e )
			);
			throw $e;
		}

		do {
			$this->getJob();
			if (!$this->currentJob) { continue; }

			$isValid = $this->validateParams( $this->params );
			if ( !$isValid ) {
				$this->log->error("Job missing required params is being deleted!");
				$this->deleteJob();
				continue;
			}
			try {
				$this->log->debug( "Processing job" );
				$this->result = $this->process( $this->params );
				$this->postProcess( $this->currentJob );
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
		$this->log->info("Worker stopped");
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
			if (is_string($value)) {
				$value = trim($value);
			}
			$params[$key] = $value;
		}

		return $params;
	}
	
	/**
	 * Hook to allow any extensions to do things after processing a job
	 * @param \Pheanstalk_Job $job
	 * @since 1.5.0
	 */
	protected function postProcess( $job ) { }

	/**
	 * Pheanstalk is setup here.
	 * Do NOT override this; instead override setup.
	 */
	public function __construct() {
		$this->log = $this->getLogger();
	}

	private function getJob() {
		$this->checkForTerminationSignal();

		# if last reserve timed out we don't want to spam the log
		# null check for first job
		if ($this->currentJob || $this->currentJob === null) {
			$this->log->debug( "Getting next job..." );
		}

		try {
			$this->currentJob = $this->pheanstalk->reserveFromTube( $this->getTubeName(), 5 );
		} catch (\Pheanstalk_Exception_SocketException $e) {
			$this->currentJob = false;
		}

		$this->checkForTerminationSignal();

		if (!$this->currentJob) {
			return;
		}

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
					"Job is missing required param: \"$reqParam\"",
					array( "params" => $params )
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
		$this->jobErrors = Collection::increment($this->jobErrors, $id);
		$numErrors = $this->jobErrors[$id];

		$this->log->warning($e->getMessage());
		$this->log->warning(
			"Job failed $numErrors times.",
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
	 * @param \Exception $e
	 * @deprecated Use {@see \GMO\Beanstalk\AbstractWorker::handleError()} instead
	 * @todo Remove in 2.0.0
	 */
	private function handleRetry( $e ) {
		$id = $this->currentJob->getId();
		# Increment job id errors
		$this->jobErrors = Collection::increment($this->jobErrors, $id);
		$numErrors = $this->jobErrors[$id];

		$this->log->warning($e->getMessage());
		$this->log->warning(
			"Job failed $numErrors times.",
			array(
			     "params" => $this->params,
			     "exception" => $e
			)
		);

		if ( $numErrors > 2 ) {
			$this->log->error("Job failed 3 times...deleting.");
			$this->deleteJob();
		}
	}

	/**
	 * Job should not be retried and is deleted.
	 * @param \Exception $e
	 */
	private function handleFatal( $e ) {
		$this->log->warning($e->getMessage());
		$this->log->warning(
			"Not retrying job...deleting.",
			array(
			     "params" => $this->params,
			     "exception" => $e
			)
		);
		$this->deleteJob();
	}

	private function deleteJob() {
		$this->log->debug( "Deleting the current job from: " . $this->getTubeName() );
		try {
			$this->pheanstalk->delete( $this->currentJob );
		} catch ( \Pheanstalk_Exception_ServerException $e ) {
			$this->log->warning( "Error deleting job", array("exception" => $e) );
		}
	}

	private function signalHandler($signalNum) {
		$this->keepRunning = false;
	}

	private function checkForTerminationSignal() {
		pcntl_signal_dispatch();
	}

	/**
	 * Associative array of job ids with the number
	 * of times they've thrown an exception
	 * @var array
	 */
	protected $jobErrors = array();

	/** @var \Pheanstalk_Job Current job being processed */
	protected $currentJob;

	/** @var mixed Results of call to process() */
	protected $result;
	
	/** @var LoggerInterface Worker logger */
	protected $log;

	/** @var array Current Job JSON decoded array */
	private $params;

	/** @var \Pheanstalk_Pheanstalk */
	private $pheanstalk;

	/** @var bool Boolean for running loop */
	private $keepRunning = true;
}
