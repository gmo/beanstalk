<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue\QueueInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ChangeStateCommand extends AbstractQueueCommand {

	abstract protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output);

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);

		if ($this->validateState) {
			if (!$input->getOption('ready') && !$input->getOption('buried') && !$input->getOption('delayed')) {
				throw new \RuntimeException('One or more states must be specified. (ready, buried, and/or delayed)');
			}
		}
		if ($this->validateTube) {
			if (!$input->getOption('all') && !$input->getArgument('tube')) {
				throw new \RuntimeException('One or more tubes must be specified.');
			}
		}

		$queue = $this->getQueue($input);
		if ($input->getOption('all')) {
			$error = false;
			$tubes = $queue->listTubes();
			if ($tubes->isEmpty()) {
				$output->writeln('There are no current tubes');
			}
		} else {
			list($tubes, $error) = $this->matchTubeNames($input->getArgument('tube'), $input, $output);
		}
		foreach ($tubes as $tube) {
			$this->forEachTube($queue, $tube, $input, $output);
		}

		if ($error) {
			$output->writeln('');
			$this->callCommand($output, ListCommand::NAME);
		}
	}

	protected function addTubeArgument() {
		$this->validateTube();
		return $this
			->addArgument('tube', InputArgument::IS_ARRAY, 'The name of the tube(s) (does not have to be exact)')
			->addOption('all', 'a', InputOption::VALUE_NONE, ucfirst($this->getName()) . ' jobs in ALL tubes');
	}

	protected function addStateOptions() {
		$this->validateState();
		$name = ucfirst($this->getName());
		return $this
			->addOption('ready', 'r', InputOption::VALUE_NONE, "$name jobs in ready state")
			->addOption('buried', 'b', InputOption::VALUE_NONE, "$name jobs in buried state")
			->addOption('delayed', 'd', InputOption::VALUE_NONE, "$name jobs in delayed state");
	}

	protected function addNumberOption() {
		return $this->addOption('number', 'n', InputOption::VALUE_REQUIRED, 'Number of jobs to ' . $this->getName(), -1);
	}

	protected function validateState($enable = true) {
		$this->validateState = $enable;
		return $this;
	}

	protected function validateTube($enable = true) {
		$this->validateTube = $enable;
		return $this;
	}

	private $validateState = false;
	private $validateTube = false;
}
