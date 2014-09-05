<?php
namespace GMO\Beanstalk;

class Processor {

	/**
	 * @param int|string $pid
	 * @param int $interval in milliseconds
	 */
	public function waitForProcess($pid, $interval = 200) {
		while ($this->isProcessRunning($pid)) {
			usleep($interval * 1000); // convert to milliseconds
		}
	}

	/**
	 * Checks if pid is running
	 * @param $pid
	 * @return bool
	 */
	public function isProcessRunning($pid) {
		$this->execute("ps $pid", $lines, $exitCode);
		return $exitCode === 0;
	}

	public function grepForPids($grep) {
		$results = array();
		$this->execute("ps ax -o pid,command | grep -v grep | grep \"$grep\"", $processes);
		foreach ($processes as $process) {
			if (!preg_match_all('/"[^"]+"|\S+/', $process, $matches)) {
				continue;
			}
			$parts = $matches[0];
			$results[] = array($parts[4], $parts[0]);
		}
		return $results;
	}

	public function executeFromDir($command, $dir) {
		$cwd = getcwd();
		chdir($dir);
		$this->execute($command);
		chdir($cwd);
	}

	public function execute($command, array &$output = null, &$return_var = null) {
		exec($command, $output, $return_var);
	}
}
