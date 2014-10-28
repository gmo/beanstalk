<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\Manager\WorkerManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends AbstractWorkerCommand {

	protected function configure() {
		parent::configure();
		$this->setName('stop')->setDescription('Stop workers');
	}

	protected function executeManagerFunction(InputInterface $input, OutputInterface $output, WorkerManager $manager, $workers) {
		$manager->stopWorkers($workers);
	}
}
