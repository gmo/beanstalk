<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console;

use Bolt\Common\Str;
use Gmo\Beanstalk\Bridge;
use Gmo\Common\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class QueueConsoleApplication extends Application
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('Queue');

        $this->setProjectDirectory(__DIR__ . '/../..');
        $this->setPackageName('gmo/beanstalk');

        foreach (static::getCommands() as $command) {
            $command->setName(Str::removeFirst($command->getName(), 'queue:'));

            $this->add($command);
        }
    }

    protected function getDefaultInputDefinition()
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('host', null, InputOption::VALUE_REQUIRED, 'Override beanstalk host'),
            new InputOption('port', null, InputOption::VALUE_REQUIRED, 'Override beanstalk port'),
            new InputOption('dir', null, InputOption::VALUE_REQUIRED, 'Override worker directory'),
            new InputOption('help', '-h', InputOption::VALUE_NONE, 'Display this help message.'),
            new InputOption(
                'verbose',
                '-v|vv|vvv',
                InputOption::VALUE_NONE,
                'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'
            ),
            new InputOption('version', '-V', InputOption::VALUE_NONE, 'Display this application version.'),
        ]);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public static function getCommands()
    {
        return [
            new Command\Queue\ListCommand(),
            new Command\Queue\KickCommand(),
            new Command\Queue\DeleteCommand(),
            new Command\Queue\BuryCommand(),
            new Command\Queue\PeekCommand(),
            new Command\Queue\PauseCommand(),
            new Command\Queue\StatsCommand(),
            new Command\Queue\ServerStatsCommand(),
            new Command\Queue\JobStatsCommand(),
            new Command\Worker\StartCommand(),
            new Command\Worker\StopCommand(),
            new Command\Worker\RestartCommand(),
            new Command\Worker\StatsCommand(),
        ];
    }
}
