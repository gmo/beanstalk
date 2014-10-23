<?php
namespace GMO\Beanstalk\Manager;

use GMO\Beanstalk\Helper\ClassFinder;
use GMO\Beanstalk\Helper\Processor;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\String;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * WorkerManager controls beanstalk workers.
 */
class WorkerManager implements LoggerAwareInterface {

	/**
	 * Restarts all beanstalk workers
	 * @param null|string|array $filter [optional] worker(s) filter
	 */
	public function restartWorkers($filter = null) {
		$this->stopWorkers($filter);
		$this->startWorkers($filter);
	}

	/**
	 * Spawns workers of each type up to the number of
	 * workers specified in each worker class.
	 * @param null|string|array $filter [optional] worker(s) filter
	 */
	public function startWorkers($filter = null) {
		$this->log->info("Starting workers...");
		# get workers
		$workers = $this->getWorkers($filter);

		foreach ($workers as $worker) {
			$workersToSpawn = $worker->getTotal() - $worker->getNumRunning();

			if ($workersToSpawn > 0) {
				$this->log->info("Starting $workersToSpawn workers: " . $worker->getName());
			}
			# spawn new workers
			for ($i = 0; $i < $workersToSpawn; $i++) {
				$this->spawnWorker($worker);
			}
		}
	}

	/**
	 * Spawn a new worker given the class name
	 * @param string $worker class name
	 */
	public function startWorker($worker) {
		if (!file_exists($this->workerDir . $worker . ".php")) {
			$this->log->error("Worker: $worker doesn't exist");
			return;
		}

		$worker = new WorkerInfo($this->getPhpClass($this->workerDir . $worker . '.php'));

		$this->log->info("Starting worker: " . $worker->getName());
		$this->spawnWorker($worker);
	}

	/**
	 * Stops all beanstalk workers
	 * @param null|string|array $filter [optional] worker(s) filter
	 */
	public function stopWorkers($filter = null) {
		$workers = $this->getWorkers($filter);
		foreach ($workers as $worker) {
			if (count($worker->getPids()) === 0) {
				continue;
			}
			$this->log->info("Stopping workers: " . $worker->getName());
			foreach ($worker->getPids() as $pid) {
				$this->log->debug(sprintf("Terminating: [%s] %s", $pid, $worker->getName()));
				posix_kill($pid, SIGTERM);
			}
		}
		foreach ($workers as $worker) {
			foreach ($worker->getPids() as $pid) {
				$this->log->debug(sprintf("Waiting for: [%s] %s...", $pid, $worker->getName()));
				$this->processor->waitForProcess($pid);
				$worker->removePid($pid);
			}
		}
	}

	/**
	 * Get a collection of {@see WorkerInfo}'s that implement {@see WorkerInterface}
	 * @param null|string|array $filter [optional] worker(s) filter
	 * @return WorkerInfo[]|ArrayCollection
	 */
	public function getWorkers($filter = null) {
		$self = $this;
		return ArrayCollection::create(
			ClassFinder::create($this->workerDir)
			->isInstantiable()
			->isSubclassOf('\GMO\Beanstalk\Worker\WorkerInterface')
			->map(function(\ReflectionClass $class) {
				return new WorkerInfo($class);
			}))
			->filter(function(WorkerInfo $worker) use ($self, $filter) {
				return $self->filterWorkers($worker->getName(), $filter);
			})
			->map(function(WorkerInfo $worker) use ($self) {
				$processes = $self->getProcessor()->grepForPids(sprintf('runner \"%s\"', $self->getWorkerDir()));

				foreach ($processes as $process) {
					if ($worker->getFullyQualifiedName() === $process[0]) {
						$worker->addPid($process[1]);
					}
				}
				return $worker;
			});
	}

	public function getWorkerDir() {
		return $this->workerDir;
	}

	public function getProcessor() {
		return $this->processor;
	}

	/** @inheritdoc */
	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
	}

	protected function spawnWorker(WorkerInfo $worker) {
		//TODO: Use actual logger not redirection
		$cmd =
			sprintf('%s "\"%s\"" "%s" %s %d >> /var/log/gmo/beanstalkd/%s.log 2>&1 &', './runner', $this->workerDir,
				$worker->getFullyQualifiedName(), $this->host, $this->port, $worker->getName());
		$this->processor->executeFromDir($cmd, __DIR__ . '/../../bin');
	}

	/**
	 * Fuzzy matching for worker name against one or multiple filters
	 * @param string            $workerName
	 * @param null|string|array $filters worker(s) filter
	 * @return bool
	 */
	public function filterWorkers($workerName, $filters) {
		if (!$filters) {
			return true;
		}
		if (!is_array($filters)) {
			$filters = array( $filters );
		}
		foreach ($filters as $filter) {
			if (String::contains($workerName, $filter, false)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get first class name from file
	 * @param string $file
	 * @return string|false fully qualified class name
	 */
	private static function getPhpClass($file) {
		$parser = new \PHPParser_Parser(new \PHPParser_Lexer());
		$phpCode = file_get_contents($file);
		try {
			$stmts = $parser->parse($phpCode);
		} catch (\PHPParser_Error $e) {
			return false;
		}
		foreach ($stmts as $stmt) {
			if ($stmt instanceof \PHPParser_Node_Stmt_Namespace) {
				$namespace = implode("\\", $stmt->name->parts);
				foreach($stmt->stmts as $subStmt) {
					if ($subStmt instanceof \PHPParser_Node_Stmt_Class) {
						return $namespace . "\\" . $subStmt->name;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param string          $workerDir Directory containing workers
	 * @param LoggerInterface $logger
	 * @param string          $host      Beanstalkd host
	 * @param int             $port      Beanstalkd port
	 * @param Processor       $processor
	 */
	public function __construct(
		$workerDir,
		LoggerInterface $logger = null,
		$host = 'localhost',
		$port = 11300,
		Processor $processor = null
	) {
		$this->workerDir = $workerDir ? realpath($workerDir) . "/" : null;
		$this->processor = $processor ?: new Processor();
		$this->setLogger($logger ?: new NullLogger());
		$this->host = $host;
		$this->port = $port;
	}

	/** @var string Directory containing workers */
	protected $workerDir;
	/** @var Processor */
	protected $processor;
	/** @var LoggerInterface */
	protected $log;
	/** @var string */
	protected $host;
	/** @var int */
	protected $port;
}
