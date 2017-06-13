<?php

namespace Gmo\Beanstalk\Tests\Manager;

use GMO\Beanstalk\Helper\Processor;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Str;

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
        $this->executeCalls = new ArrayCollection();
        $this->terminatedProcesses = new ArrayCollection();
        $this->terminatedForcefullyProcesses = new ArrayCollection();
        $this->waitedForProcesses = new ArrayCollection();
    }

    public function waitForProcess($pid, $interval = 200, $timeout = 10)
    {
        $this->waitedForProcesses->add($pid);
    }

    public function isProcessRunning($pid)
    {
        return $this->getProcesses()
            ->exists(function ($key, $value) use ($pid) {
                return $value[1] == $pid;
            })
        ;
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
            ->filter(function ($line) use ($grep) {
                return Str::contains($line, $grep, false);
            })
            ->map(array($this, 'parseLines'))
            ->map(function ($line) {
                return array($line[13], $line[1]);
            })
        ;
    }

    public function execute($command, array &$output = null, &$return_var = null)
    {
        $this->executeCalls->add($command);
    }

    private function getProcessLines()
    {
        $workerDir = $this->workerDir;

        return ArrayCollection::create(file(__DIR__ . '/process_list.txt'))
            ->map(function ($line) use ($workerDir) {
                return str_replace('{WORKER_DIR}', $workerDir, $line);
            })
        ;
    }

    public function parseLines($line)
    {
        if (preg_match_all('/"[^"]+"|\S+/', $line, $matches)) {
            return $matches[0];
        }

        return $line;
    }

    private function getProcesses()
    {
        return $this->getProcessLines()
            ->map(function ($line) {
                if (preg_match_all('/"[^"]+"|\S+/', $line, $matches)) {
                    return $matches[0];
                }

                return $line;
            })
        ;
    }
}
