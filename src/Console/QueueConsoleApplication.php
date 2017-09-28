<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console;

use Bolt\Common\Str;
use Gmo\Beanstalk\Bridge;
use GMO\Console\ConsoleApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class QueueConsoleApplication extends ConsoleApplication
{
    /**
     * Constructor.
     *
     * @param \Pimple|\Pimple\Container|null $container
     */
    public function __construct($container = null)
    {
        if ($container === null) {
            if (class_exists('Pimple\Container')) {
                $container = new \Pimple\Container();
                $container->register(new Bridge\Pimple3\BeanstalkServiceProvider());
            } else {
                $container = new \Pimple();
                $sp = new Bridge\Pimple1\BeanstalkServiceProvider();
                $sp->register($container);
            }
        }
        parent::__construct('Queue', null, $container);

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

    protected function getPackageName()
    {
        return 'gmo/beanstalk';
    }

    public function getProjectDirectory()
    {
        return __DIR__ . '/../..';
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
