<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Worker;

use Gmo\Common\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends AbstractWorkerCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('workers:start')
            ->addOption(
                'number',
                'm',
                InputOption::VALUE_REQUIRED,
                'Override number of workers to start <comment>(default: up to the number specified by the worker)</comment>'
            )
            ->setDescription('Start workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workers = $input->getArgument('worker');
        $number = (int) Str::removeFirst($input->getOption('number'), '=');
        $this->manager->startWorkers($workers, $number);
    }
}
