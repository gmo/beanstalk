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
	 * @param int $spawnNumber [optional] The number of workers to spawn.
	 *
	 *                         Default is to spawn workers up to the total specified by the worker
	 */
	public function startWorkers($filter = null, $spawnNumber = null) {
		$this->log->info("Starting workers...");
		$workers = $this->getWorkers($filter);

		foreach ($workers as $worker) {
			$workersToSpawn = $spawnNumber ?: ($worker->getTotal() - $worker->getNumRunning());

			if ($workersToSpawn > 0) {
				$this->log->info("Starting $workersToSpawn workers: " . $worker->getName());
			}
			for($i = 0; $i < $workersToSpawn; $i++) {
				$this->spawnWorker($worker);
			}
		}
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
				$this->processor->terminateProcess($pid);
			}
		}

		$failed = new ArrayCollection();
		foreach ($workers as $worker) {
			$failed[$worker->getName()] = new ArrayCollection();

			foreach ($worker->getPids() as $pid) {
				$this->log->debug(sprintf("Waiting for: [%s] %s...", $pid, $worker->getName()));
				if (!$this->processor->waitForProcess($pid)) {
					$failed[$worker->getName()][] = $pid;
				}
				$worker->removePid($pid);
			}
		}

		foreach ($failed as $worker => $pids) {
			foreach ($pids as $pid) {
				$this->log->warning(sprintf('Failed to gracefully stop: [%s] %s. Killing it.', $pid, $worker));
				$this->processor->terminateProcess($pid, true);
			}
		}
	}

	/**
	 * Get a collection of {@see WorkerInfo}'s that implement {@see WorkerInterface}
	 * @param null|string|array $filter [optional] worker(s) filter
	 * @return WorkerInfo[]|ArrayCollection
	 */
	public function getWorkers($filter = null) {

		$processes = $this->processor->grepForPids(sprintf('runner \"%s\"', $this->getWorkerDir()));

		return $this->getWorkerInfoList()
			->map(function(WorkerInfo $worker) use ($processes) {
				foreach ($processes as $process) {
					if ($worker->getFullyQualifiedName() === $process[0]) {
						$worker->addPid($process[1]);
					}
				}
				return $worker;
			})
			->filter(function(WorkerInfo $worker) use ($filter) {
				if (!$filter) {
					return true;
				}
				if (!is_array($filter)) {
					$filter = array($filter);
				}
				foreach ($filter as $f) {
					if (String::contains($worker->getName(), $f, false)) {
						return true;
					}
				}
				return false;
			});
	}

	protected function getWorkerInfoList() {
		if ($this->workerInfoList) {
			return $this->workerInfoList;
		}
		return $this->workerInfoList = ArrayCollection::create(
			ClassFinder::create($this->workerDir)
				->isInstantiable()
				->isSubclassOf('\GMO\Beanstalk\Worker\WorkerInterface')
				->map(function(\ReflectionClass $class) {
					return new WorkerInfo($class);
				}))
			->sortKeys();
	}

	public function getWorkerDir() {
		return $this->workerDir;
	}

	/** @inheritdoc */
	public function setLogger(LoggerInterface $logger) {
		$this->log = $logger;
	}

	public function setProcessor($processor) {
		$this->processor = $processor;
	}

	protected function spawnWorker(WorkerInfo $worker) {
		//TODO: Use actual logger not redirection
		$cmd =
			sprintf('nohup %s "\"%s\"" "%s" %s %d >> /var/log/gmo/beanstalkd/%s.log 2>&1 &', './runner', $this->workerDir,
				$worker->getFullyQualifiedName(), $this->host, $this->port, $worker->getName());
		$this->processor->executeFromDir($cmd, __DIR__ . '/../../bin');
	}

	/**
	 * @param string          $workerDir Directory containing workers
	 * @param LoggerInterface $logger
	 * @param string          $host      Beanstalkd host
	 * @param int             $port      Beanstalkd port
	 */
	public function __construct($workerDir, LoggerInterface $logger = null, $host = 'localhost', $port = 11300) {
		$this->workerDir = $workerDir ? realpath($workerDir) . "/" : null;
		$this->processor = new Processor();
		$this->setLogger($logger ?: new NullLogger());
		$this->host = $host;
		$this->port = $port;
	}

	/** @var string Directory containing workers */
	protected $workerDir;
	/** @var WorkerInfo[]|ArrayCollection */
	protected $workerInfoList;
	/** @var Processor */
	protected $processor;
	/** @var LoggerInterface */
	protected $log;
	/** @var string */
	protected $host;
	/** @var int */
	protected $port;
}
