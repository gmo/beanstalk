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
			->addArgument('delay', InputArgument::OPTIONAL, 'Pause the tube(s) for this many seconds', 0)
			->addTubeArgument()
		;
	}

	protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output) {
		$delay = intval($input->getArgument('delay'));
		$queue->pause($tube, $delay);
	}
}
