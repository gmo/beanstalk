<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KickCommand extends AbstractQueueCommand {

	protected function configure() {
		$this->setName('kick')
			->addArgument(
				'tube',
				InputArgument::IS_ARRAY,
				'The name of the tube (does not have to be exact)')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Kick all tubes')
			->setDescription('Kick buried and delayed jobs');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);

		if (!$input->getOption('all') && !$input->getArgument('tube')) {
			throw new \RuntimeException('Not enough arguments.');
		}

		list($tubes, $error) = $this->matchTubeNames($input->getArgument('tube'), $output);
		$queue = $this->getQueue();
		foreach ($tubes as $tube) {
			$kicked = $queue->kickTube($tube);
			$output->writeln("Kicked <info>$kicked</info> jobs in <info>$tube</info>");
		}

		if ($error) {
			$output->writeln('');
			$this->callCommand($output, ListCommand::NAME);
		}
	}
}
