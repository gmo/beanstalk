<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Common\String;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends AbstractWorkerCommand {

	protected function configure() {
		parent::configure();
		$this->setName('start')
			->addOption(
				'number', 'n',
				InputOption::VALUE_REQUIRED,
				'Override number of workers to start <comment>(default: up to the number specified by the worker)</comment>')
			->setDescription('Start workers');
	}

	protected function executeManagerFunction(InputInterface $input, OutputInterface $output, WorkerManager $manager, $workers) {
		$number = intval(String::removeFirst($input->getOption('number'), '='));
		$manager->startWorkers($workers, $number);
	}
}
