<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Common\String;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractQueueCommand extends AbstractCommand {

	protected function getQueue() {
		/** @var QueueInterface $queue */
		$queue = $this->getContainer()->offsetGet(BeanstalkKeys::QUEUE);
		$queue->setLogger($this->logger);
		return $queue;
	}

	protected function matchTubeNames($tubesSearch, OutputInterface $output) {
		$matchedTubes = array();
		$queue = $this->getQueue();
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
