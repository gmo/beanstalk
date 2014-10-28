<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\Manager\WorkerManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartCommand extends AbstractWorkerCommand {

	protected function configure() {
		parent::configure();
		$this->setName('restart')->setDescription('Restart workers');
	}

	protected function executeManagerFunction(InputInterface $input, OutputInterface $output, WorkerManager $manager, $workers) {
		$manager->restartWorkers($workers);
	}
}
