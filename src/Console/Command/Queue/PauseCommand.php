<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use GMO\Beanstalk\Queue\QueueInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PauseCommand extends ChangeStateCommand {

	protected function configure() {
		parent::configure();
		$this->setName('pause')
			->setDescription('Pause tubes')
			->addArgument('delay', InputArgument::REQUIRED, 'Pause the tube(s) for this many seconds')
			->addTubeArgument()
		;
	}

	protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output) {
		$delay = intval($input->getOption('delay'));
		$queue->pause($tube, $delay);
	}
}
