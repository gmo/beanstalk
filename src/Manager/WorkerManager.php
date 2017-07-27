<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Manager;

use Bolt\Collection\ImmutableBag;
use Gmo\Beanstalk\Helper\ClassFinder;
use Gmo\Beanstalk\Helper\Processor;
use Gmo\Common\Str;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * WorkerManager controls beanstalk workers.
 */
class WorkerManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var string Directory containing workers */
    protected $workerDir;
    /** @var WorkerInfo[]|ImmutableBag */
    protected $workerInfoList;
    /** @var Processor */
    protected $processor;
    /** @var string */
    protected $host;
    /** @var int */
    protected $port;

    /**
     * @param string          $workerDir Directory containing workers
     * @param LoggerInterface $logger
     * @param string          $host      Beanstalkd host
     * @param int             $port      Beanstalkd port
     */
    public function __construct($workerDir, LoggerInterface $logger = null, $host = 'localhost', $port = 11300)
    {
        if (empty($workerDir)) {
            throw new \InvalidArgumentException('Worker directory is required');
        }
        $this->workerDir = realpath($workerDir) . '/';
        $this->processor = new Processor();
        $this->setLogger($logger ?: new NullLogger());
        $this->host = $host;
        $this->port = $port;
    }

    public function getWorkerDir()
    {
        return $this->workerDir;
    }

    public function setProcessor($processor)
    {
        $this->processor = $processor;
    }

    /**
     * Restarts all beanstalk workers
     *
     * @param null|string|array $filter [optional] worker(s) filter
     */
    public function restartWorkers($filter = null)
    {
        $this->stopWorkers($filter);
        $this->startWorkers($filter);
    }

    /**
     * Spawns workers of each type up to the number of
     * workers specified in each worker class.
     *
     * @param null|string|array $filter      [optional] worker(s) filter
     * @param int               $spawnNumber [optional] The number of workers to spawn.
     *                                       Default is to spawn workers up to the total specified by the worker
     */
    public function startWorkers($filter = null, $spawnNumber = null)
    {
        $this->logger->info('Starting workers...');
        $workers = $this->getWorkers($filter);

        foreach ($workers as $worker) {
            $workersToSpawn = $spawnNumber ?: ($worker->getTotal() - $worker->getNumRunning());

            if ($workersToSpawn > 0) {
                $this->logger->info("Starting $workersToSpawn workers: " . $worker->getName());
            }
            for ($i = 0; $i < $workersToSpawn; ++$i) {
                $this->spawnWorker($worker);
            }
        }
    }

    /**
     * Stops all beanstalk workers
     *
     * @param null|string|array $filter [optional] worker(s) filter
     */
    public function stopWorkers($filter = null)
    {
        $workers = $this->getWorkers($filter);
        foreach ($workers as $worker) {
            if (count($worker->getPids()) === 0) {
                continue;
            }
            $this->logger->info('Stopping workers: ' . $worker->getName());
            foreach ($worker->getPids() as $pid) {
                $this->logger->debug(sprintf('Terminating: [%s] %s', $pid, $worker->getName()));
                $this->processor->terminateProcess($pid);
            }
        }

        $failed = [];
        foreach ($workers as $worker) {
            $failed[$worker->getName()] = [];

            foreach ($worker->getPids() as $pid) {
                $this->logger->debug(sprintf('Waiting for: [%s] %s...', $pid, $worker->getName()));
                if (!$this->processor->waitForProcess($pid)) {
                    $failed[$worker->getName()][] = $pid;
                }
                $worker->removePid($pid);
            }
        }

        foreach ($failed as $worker => $pids) {
            foreach ($pids as $pid) {
                $this->logger->warning(sprintf('Failed to gracefully stop: [%s] %s. Killing it.', $pid, $worker));
                $this->processor->terminateProcess($pid, true);
            }
        }
    }

    /**
     * Get a collection of {@see WorkerInfo}'s that implement {@see WorkerInterface}
     *
     * @param null|string|array $filter [optional] worker(s) filter
     *
     * @return WorkerInfo[]|ImmutableBag
     */
    public function getWorkers($filter = null)
    {
        $processes = $this->processor->grepForPids(sprintf('runner \"%s\"', $this->getWorkerDir()));

        return $this->getWorkerInfoList()
            ->map(function ($i, WorkerInfo $worker) use ($processes) {
                foreach ($processes as $process) {
                    if ($worker->getFullyQualifiedName() === $process[0]) {
                        $worker->addPid($process[1]);
                    }
                }

                return $worker;
            })
            ->filter(function ($i, WorkerInfo $worker) use ($filter) {
                if (!$filter) {
                    return true;
                }
                if (!is_array($filter)) {
                    $filter = [$filter];
                }
                foreach ($filter as $f) {
                    if (Str::contains($worker->getName(), $f, false)) {
                        return true;
                    }
                }

                return false;
            })
        ;
    }

    protected function getWorkerInfoList()
    {
        if ($this->workerInfoList) {
            return $this->workerInfoList;
        }

        $list = ClassFinder::create($this->workerDir)
            ->isInstantiable()
            ->isSubclassOf('\Gmo\Beanstalk\Worker\WorkerInterface')
            ->map(function (\ReflectionClass $class) {
                return new WorkerInfo($class);
            })
        ;
        $list = iterator_to_array($list);
        ksort($list);

        return $this->workerInfoList = new ImmutableBag($list);
    }

    protected function spawnWorker(WorkerInfo $worker)
    {
        //TODO: Use actual logger not redirection
        $outFile = file_exists('/var/log/gmo/beanstalkd') ?
            "/var/log/gmo/beanstalkd/{$worker->getName()}.log" :
            '/dev/null'
        ;
        $cmd = sprintf(
            'nohup %s "\"%s\"" "%s" %s %d >> %s 2>&1 &',
            './runner',
            $this->workerDir,
            $worker->getFullyQualifiedName(),
            $this->host,
            $this->port,
            $outFile
        );
        $this->processor->executeFromDir($cmd, __DIR__ . '/../../bin');
    }
}
