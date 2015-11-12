<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Tube\Tube;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractQueueCommand extends AbstractCommand {

	/**
	 * @return QueueInterface
	 */
	protected function getQueue() {
		return $this->getService(BeanstalkKeys::QUEUE);
	}

	protected function matchTubeNames($tubesSearch, OutputInterface $output) {
		$matchedTubes = new ArrayCollection();
		$queue = $this->getQueue();
		$error = false;
		foreach ($tubesSearch as $tubeSearch) {
			$matched = $queue
				->tubes()
				->filter(function(Tube $tube) use ($tubeSearch) {
					return Str::contains($tube->name(), $tubeSearch, false);
				});
			if ($matched->isEmpty()) {
				$output->writeln("<warn>No tubes matched to: $tubeSearch</warn>");
				$error = true;
			}
			$matchedTubes->merge($matched);
		}
		return array($matchedTubes, $error);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);
		$this->setupQueue($input);
	}

	private function setupQueue(InputInterface $input) {
		$container = $this->getContainer();
		if ($host = $input->getOption('host')) {
			$container[BeanstalkKeys::HOST] = $host;
		}
		if ($port = $input->getOption('port')) {
			$container[BeanstalkKeys::PORT] = $port;
		}

		$logger = $this->logger;
		$container[BeanstalkKeys::QUEUE] = $container->share($container->extend(BeanstalkKeys::QUEUE, function (QueueInterface $queue) use ($logger) {
			$queue->setLogger($logger);
			return $queue;
		}));
	}
}
