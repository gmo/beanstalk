<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Manager\WorkerManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractWorkerCommand extends AbstractCommand {

	protected function configure() {
		parent::configure();
		$this->addArgument(
			'worker',
			InputArgument::IS_ARRAY,
			'The name of the worker (does not have to be exact) <comment>(default: all workers)</comment>'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);
		$this->executeManagerFunction($output, $this->getManager($input), $input->getArgument('worker'));
	}

	protected function executeManagerFunction(OutputInterface $output, WorkerManager $manager, $workers) { }

	protected function getManager(InputInterface $input) {
		$container = $this->getContainer();
		if ($host = $input->getOption('host')) {
			$container[BeanstalkKeys::HOST] = $host;
		}
		if ($port = $input->getOption('port')) {
			$container[BeanstalkKeys::PORT] = $port;
		}
		if ($dir = $input->getOption('dir')) {
			$container[BeanstalkKeys::WORKER_DIRECTORY] = $dir;
		}

		/** @var WorkerManager $manager */
		$manager = $container[BeanstalkKeys::WORKER_MANAGER];
		$manager->setLogger($this->logger);
		return $manager;
	}
}
