<?php
namespace GMO\Beanstalk\Console;

use GMO\Beanstalk\Console\Command\Queue as Command;
use GMO\Console\ConsoleApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class QueueConsoleApplication extends ConsoleApplication {

	public function __construct(\Pimple $container = null) {
		parent::__construct('Queue', 'DEV', $container);
		$this->addCommands(array(
			new Command\ListCommand(),
			new Command\KickCommand(),
			new Command\DeleteCommand(),
			new Command\StatsCommand(),
			new Command\ServerStatsCommand(),
		));
	}

	protected function getDefaultInputDefinition() {
		return new InputDefinition(array(
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

			new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'Display this help message.'),
		));
	}
}
