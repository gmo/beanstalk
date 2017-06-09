<?php

namespace GMO\Beanstalk\Console;

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
        $container['beanstalk.console_commands.queue_prefix'] = '';
        parent::__construct('Queue', null, $container);
        $this->addCommands($container['beanstalk.console_commands']);
    }

    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
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
        ));
    }

    protected function getPackageName()
    {
        return 'gmo/beanstalk';
    }

    public function getProjectDirectory()
    {
        return __DIR__ . '/../..';
    }
}
