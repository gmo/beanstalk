<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Common\String;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractQueueCommand extends AbstractCommand {

	protected function getQueue(InputInterface $input) {
		$container = $this->getContainer();
		if ($host = $input->getOption('host')) {
			$container[BeanstalkKeys::HOST] = $host;
		}
		if ($port = $input->getOption('port')) {
			$container[BeanstalkKeys::PORT] = $port;
		}

		/** @var QueueInterface $queue */
		$queue = $container[BeanstalkKeys::QUEUE];
		$queue->setLogger($this->logger);
		return $queue;
	}

	protected function matchTubeNames($tubesSearch, InputInterface $input, OutputInterface $output) {
		$matchedTubes = array();
		$queue = $this->getQueue($input);
		$error = false;
		foreach ($tubesSearch as $tubeSearch) {
			$matched = $queue
				->listTubes()
				->filter(function($tubeName) use ($tubeSearch) {
					return String::contains($tubeName, $tubeSearch, false);
				});
			if ($matched->isEmpty()) {
				$output->writeln("<warn>No tubes matched to: $tubeSearch</warn>");
				$error = true;
			}
			$matchedTubes = array_merge($matchedTubes, $matched->toArray());
		}
		return array($matchedTubes, $error);
	}
}
