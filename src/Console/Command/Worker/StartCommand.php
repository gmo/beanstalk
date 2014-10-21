<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\Manager\WorkerManager;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends AbstractWorkerCommand {

	protected function configure() {
		parent::configure();
		$this->setName('start')->setDescription('Start workers');
	}

	protected function executeManagerFunction(OutputInterface $output, WorkerManager $manager, $workers) {
		$manager->startWorkers($workers);
	}
}
