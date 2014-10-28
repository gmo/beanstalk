<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends AbstractQueueCommand {

	protected function configure() {
		parent::configure();
		$this->setName('delete')
			->addArgument(
				'tube',
				InputArgument::IS_ARRAY,
				'The name of the tube (does not have to be exact)')
			->addOption('ready', 'r', InputOption::VALUE_NONE, 'Delete jobs in ready state')
			->addOption('buried', 'b', InputOption::VALUE_NONE, 'Delete jobs in buried state')
			->addOption('delayed', 'd', InputOption::VALUE_NONE, 'Delete jobs in delayed state')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Delete jobs in ALL tubes')
			->setDescription('Delete jobs');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);

		if (!$input->getOption('ready') && !$input->getOption('buried') && !$input->getOption('delayed')) {
			throw new \RuntimeException('One or more states must be specified.');
		}

		if (!$input->getOption('all') && !$input->getArgument('tube')) {
			throw new \RuntimeException('One or more tubes must be specified.');
		}

		$queue = $this->getQueue($input);

		list($tubes, $error) = $this->matchTubeNames($input->getArgument('tube'), $input, $output);
		if ($input->getOption('all')) {
			$tubes = $queue->listTubes();
			if ($tubes->isEmpty()) {
				$output->writeln('There are no current tubes');
			}
		}

		foreach ($tubes as $tube) {
			if ($input->getOption('ready')) {
				$queue->deleteReadyJobs($tube);
				$output->writeln("Deleted <info>ready</info> jobs in <info>$tube</info>");
			}
			if ($input->getOption('buried')) {
				$queue->deleteReadyJobs($tube);
				$output->writeln("Deleted <info>buried</info> jobs in <info>$tube</info>");
			}
			if ($input->getOption('delayed')) {
				$queue->deleteReadyJobs($tube);
				$output->writeln("Deleted <info>delayed</info> jobs in <info>$tube</info>");
			}
		}

		if ($error) {
			$output->writeln('');
			$this->callCommand($output, ListCommand::NAME);
		}
	}
}
