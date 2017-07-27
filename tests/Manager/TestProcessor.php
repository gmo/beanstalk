<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Tests\Manager;

use Bolt\Collection\Bag;
use Gmo\Beanstalk\Helper\Processor;
use Gmo\Common\Str;

class TestProcessor extends Processor
{
    private $workerDir;
    public $executeCalls;
    public $terminatedProcesses;
    public $terminatedForcefullyProcesses;
    public $waitedForProcesses;

    public function __construct($workerDir)
    {
        $this->workerDir = realpath($workerDir) . '/';
        $this->executeCalls = new Bag();
        $this->terminatedProcesses = new Bag();
        $this->terminatedForcefullyProcesses = new Bag();
        $this->waitedForProcesses = new Bag();
    }

    public function waitForProcess($pid, $interval = 200, $timeout = 10)
    {
        $this->waitedForProcesses->add($pid);
    }

    public function isProcessRunning($pid)
    {
        foreach ($this->getProcesses() as $process) {
            if ($process[1] === $pid) {
                return true;
            }
        }

        return false;
    }

    public function terminateProcess($pid, $force = false)
    {
        if ($force) {
            $this->terminatedForcefullyProcesses->add($pid);
        } else {
            $this->terminatedProcesses->add($pid);
        }
    }

    public function grepForPids($grep)
    {
        $grep = str_replace('\"', '"', $grep);

        return $this->getProcessLines()
            ->filter(function ($i, $line) use ($grep) {
                return Str::contains($line, $grep, false);
            })
            ->map([$this, 'parseLines'])
            ->map(function ($i, $line) {
                return [$line[13], $line[1]];
            })
        ;
    }

    public function execute($command, array &$output = null, &$exitCode = null)
    {
        $this->executeCalls->add($command);
    }

    private function getProcessLines()
    {
        $workerDir = $this->workerDir;

        return (new Bag(file(__DIR__ . '/process_list.txt')))
            ->map(function ($i, $line) use ($workerDir) {
                return str_replace('{WORKER_DIR}', $workerDir, $line);
            })
        ;
    }

    public function parseLines($i, $line)
    {
        if (preg_match_all('/"[^"]+"|\S+/', $line, $matches)) {
            return $matches[0];
        }

        return $line;
    }

    private function getProcesses()
    {
        return $this->getProcessLines()
            ->map(function ($i, $line) {
                if (preg_match_all('/"[^"]+"|\S+/', $line, $matches)) {
                    return $matches[0];
                }

                return $line;
            })
        ;
    }
}
