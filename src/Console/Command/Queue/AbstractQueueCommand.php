<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\BeanstalkServiceProvider;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Common\String;
use GMO\Console\ContainerAwareCommand;
use GMO\DependencyInjection\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractQueueCommand extends ContainerAwareCommand {

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);
		$this->logger = new ConsoleLogger($output);
		$output->getFormatter()->setStyle('warn', new OutputFormatterStyle('red'));
	}

	protected function getQueue() {
		/** @var QueueInterface $queue */
		$queue = $this->getContainer()->offsetGet(BeanstalkKeys::QUEUE);
		$queue->setLogger($this->logger);
		return $queue;
	}

	public function getConsoleWidth() {
		$app = $this->getApplication();
		if (!$app) {
			return null;
		}
		$dimensions = $app->getTerminalDimensions();
		return $dimensions[0];
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

	protected function getDefaultContainer(Container $container) {
		$container = parent::getDefaultContainer($container);
		$container->registerService(new BeanstalkServiceProvider());
		return $container;
	}

	/** @var LoggerInterface */
	protected $logger;
}
