<?php
namespace GMO\Beanstalk\Console;

use GMO\Beanstalk\Console\Command;
use GMO\Console\ConsoleApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class QueueConsoleApplication extends ConsoleApplication {

	public function __construct(\Pimple $container = null) {
		parent::__construct('Queue', null, $container);
		$this->addCommands(array(
			new Command\Queue\ListCommand(),
			new Command\Queue\KickCommand(),
			new Command\Queue\DeleteCommand(),
			new Command\Queue\PeekCommand(),
			new Command\Queue\StatsCommand(),
			new Command\Queue\ServerStatsCommand(),
			new Command\Queue\JobStatsCommand(),
			new Command\Worker\StartCommand(),
			new Command\Worker\StopCommand(),
			new Command\Worker\RestartCommand(),
			new Command\Worker\StatsCommand(),
		));
	}

	protected function getDefaultInputDefinition() {
		return new InputDefinition(array(
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

			new InputOption('host',     null, InputOption::VALUE_REQUIRED, 'Override beanstalk host'),
			new InputOption('port',     null, InputOption::VALUE_REQUIRED, 'Override beanstalk port'),
			new InputOption('dir',	    null, InputOption::VALUE_REQUIRED, 'Override worker directory'),
			new InputOption('help',     '-h', InputOption::VALUE_NONE,     'Display this help message.'),
			new InputOption('verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
			new InputOption('version',  '-V', InputOption::VALUE_NONE, 'Display this application version.'),
		));
	}

	protected function getPackageName() { return 'gmo/beanstalk'; }

	protected function getProjectDirectory() { return __DIR__ . '/../..'; }
}
