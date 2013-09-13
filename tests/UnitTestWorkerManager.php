<?php

use GMO\Beanstalk\WorkerManager;

class UnitTestWorkerManager extends WorkerManager
{
	public $calledCommands = array();
	public $calledWorkers = array();

	public function startWorker($worker) {
		if (!isset($this->calledWorkers[$worker])) {
			$this->calledWorkers[$worker] = 0;
		}
		$this->calledWorkers[$worker]++;
	}

	protected function execute($command, array &$output = null, &$return_var = null)  {
		$this->calledCommands[] = $command;
		# Inject our own process list
		$command = str_replace("ps aux", "cat ".__DIR__."/process_list.txt", $command);
		#
		$command = str_replace($this->workerDir, "{WORKER_DIR}", $command);
		# Only execute command if specified
		$tempOut = array();
		if (strpos($command, "kill") !== 0) {
			parent::execute($command, $tempOut, $return_var);
		}
		foreach ($tempOut as $line) {
			$line = str_replace("{WORKER_DIR}", $this->workerDir, $line);
			$output[] = $line;
		}
	}
}