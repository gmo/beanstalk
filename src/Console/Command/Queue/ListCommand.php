<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractQueueCommand {

	const NAME = 'tubes';

	protected function configure() {
		parent::configure();
		$this->setName(static::NAME)
			->setDescription('Get list of tubes');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);

		$tubes = $this->getQueue()->tubes();
		if ($tubes->isEmpty()) {
			$output->writeln("There are no current tubes");
			return;
		}

		$output->writeln("Current tubes:");
		foreach ($tubes as $tube) {
			$output->writeln(' - ' . $tube->name());
		}
	}
}
