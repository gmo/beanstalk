<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use GMO\Beanstalk\Queue\QueueInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KickCommand extends ChangeStateCommand {

	protected function configure() {
		parent::configure();
		$this->setName('kick')
			->setDescription('Kick buried and delayed jobs')
			->addTubeArgument()
			->addNumberOption()
		;
	}

	protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output) {
		$number = intval($input->getOption('number'));
		$kicked = $queue->kickTube($tube, $number);
		$output->writeln("Kicked <info>$kicked</info> jobs in <info>$tube</info>");
	}
}
