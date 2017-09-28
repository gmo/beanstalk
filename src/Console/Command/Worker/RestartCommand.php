<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Worker;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartCommand extends AbstractWorkerCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('workers:restart')
            ->setDescription('Restart workers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workers = $input->getArgument('worker');
        $this->manager->restartWorkers($workers);
    }
}
