<?php
namespace GMO\Beanstalk\Console;

use GMO\Beanstalk\Console\Command;
use GMO\Console\ConsoleApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class QueueConsoleApplication extends ConsoleApplication {

	public function __construct(\Pimple $container = null) {
		parent::__construct('Queue', 'DEV', $container);
		$this->addCommands(array(
			new Command\Queue\ListCommand(),
			new Command\Queue\KickCommand(),
			new Command\Queue\DeleteCommand(),
			new Command\Queue\StatsCommand(),
			new Command\Queue\ServerStatsCommand(),
			new Command\Worker\StartCommand(),
			new Command\Worker\StopCommand(),
			new Command\Worker\RestartCommand(),
			new Command\Worker\StatsCommand(),
		));
	}

	protected function getDefaultInputDefinition() {
		return new InputDefinition(array(
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

			new InputOption('--help',   '-h', InputOption::VALUE_NONE,     'Display this help message.'),
			new InputOption('host',     null, InputOption::VALUE_REQUIRED, 'Override beanstalk host'),
			new InputOption('port',     null, InputOption::VALUE_REQUIRED, 'Override beanstalk port'),
			new InputOption('dir',	    null, InputOption::VALUE_REQUIRED, 'Override worker directory'),
		));
	}
}
