<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Helper;

use Carbon\Carbon;

class Processor
{
    /**
     * @param int|string $pid
     * @param int        $interval in milliseconds
     * @param int        $timeout  in seconds
     *
     * @return bool Whether the process stopped or the timeout was hit
     */
    public function waitForProcess($pid, $interval = 200, $timeout = 10)
    {
        $start = Carbon::now();
        while ($this->isProcessRunning($pid)) {
            if ($start->diffInSeconds() >= $timeout) {
                return false;
            }
            usleep($interval * 1000); // convert to milliseconds
        }

        return true;
    }

    public function terminateProcess($pid, $force = false)
    {
        posix_kill($pid, $force ? SIGKILL : SIGTERM);
    }

    /**
     * Checks if pid is running.
     *
     * @param $pid
     *
     * @return bool
     */
    public function isProcessRunning($pid)
    {
        $this->execute("ps $pid", $lines, $exitCode);

        return $exitCode === 0;
    }

    public function grepForPids($grep)
    {
        $results = [];
        $this->execute("ps ax -o pid,command | grep -v grep | grep \"$grep\"", $processes);
        foreach ($processes as $process) {
            if (!preg_match_all('/"[^"]+"|\S+/', $process, $matches)) {
                continue;
            }
            $parts = $matches[0];
            $results[] = [$parts[4], $parts[0]];
        }

        return $results;
    }

    public function executeFromDir($command, $dir)
    {
        $cwd = getcwd();
        chdir($dir);
        $this->execute($command);
        chdir($cwd);
    }

    public function execute($command, array &$output = null, &$exitCode = null)
    {
        exec($command, $output, $exitCode);
    }
}
