<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Manager\WorkerManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractWorkerCommand extends AbstractCommand {

	public function setName($name) {
		return parent::setName("workers:$name");
	}

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
		$manager = $this->getManager($input);
		if (!$manager->getWorkerDir()) {
			throw new \RuntimeException('Worker directory needs to be passed in via --dir or set in the dependency container');
		}
		$this->executeManagerFunction($input, $output, $manager, $input->getArgument('worker'));
	}

	protected function executeManagerFunction(InputInterface $input, OutputInterface $output, WorkerManager $manager, $workers) { }

	protected function getManager(InputInterface $input) {
		$container = $this->getContainer();
		if ($input->hasOption('host') && $host = $input->getOption('host')) {
			$container[BeanstalkKeys::HOST] = $host;
		}
		if ($input->hasOption('port') && $port = $input->getOption('port')) {
			$container[BeanstalkKeys::PORT] = $port;
		}
		if ($input->hasOption('dir') && $dir = $input->getOption('dir')) {
			$container[BeanstalkKeys::WORKER_DIRECTORY] = $dir;
		}

		/** @var WorkerManager $manager */
		$manager = $container[BeanstalkKeys::WORKER_MANAGER];
		$manager->setLogger($this->logger);
		return $manager;
	}
}
