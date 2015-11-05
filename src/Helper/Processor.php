<?php
namespace GMO\Beanstalk\Helper;

use Carbon\Carbon;

class Processor {

	const WORKER_USERNAME = 'alfred';

	/**
	 * @param int|string $pid
	 * @param int        $interval in milliseconds
	 * @param int        $timeout  in seconds
	 * @return bool Whether the process stopped or the timeout was hit
	 */
	public function waitForProcess($pid, $interval = 200, $timeout = 10) {
		$start = Carbon::now();
		while ($this->isProcessRunning($pid)) {
			if ($start->diffInSeconds() >= $timeout) {
				return false;
			}
			usleep($interval * 1000); // convert to milliseconds
		}
		return true;
	}

	public function terminateProcess($pid, $force = false) {
		$cmd =
			sprintf('kill -%d %d',
				$force ? SIGKILL : SIGTERM, $pid
			);

		if(!$this->isCurrentUserTheWorkerUser()) {
			$cmd = $this->getSwitchUserCommand($cmd);
		}

		$this->execute($cmd);
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

	public function isCurrentUserTheWorkerUser() {
		$workerUserExists = is_array(posix_getpwnam(static::WORKER_USERNAME));
		return !$workerUserExists || static::getCurrentUsername() === static::WORKER_USERNAME;
	}

	public function getCurrentUsername() {
		$userData = posix_getpwuid(posix_geteuid());
		return $userData['name'];
	}

	public function getSwitchUserCommand($command) {
		return sprintf('sudo -u %s bash -c \'%s\'',
			static::WORKER_USERNAME, $command
		);
	}

}
