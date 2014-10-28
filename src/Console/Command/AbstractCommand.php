<?php
namespace GMO\Beanstalk\Console\Command;

use GMO\Beanstalk\BeanstalkServiceProvider;
use GMO\Console\ContainerAwareCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractCommand extends ContainerAwareCommand {

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->logger = new ConsoleLogger($output);
		$output->getFormatter()->setStyle('warn', new OutputFormatterStyle('red'));
	}

	public function getConsoleWidth() {
		$app = $this->getApplication();
		if (!$app) {
			return null;
		}
		$dimensions = $app->getTerminalDimensions();
		return $dimensions[0];
	}

	protected function getDefaultContainer() {
		return parent::getDefaultContainer()
			->registerService(new BeanstalkServiceProvider());
	}

	/** @var LoggerInterface */
	protected $logger;
}
