<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractQueueCommand
{
    protected function configure()
    {
        $this->setName('queue:tubes')
            ->setDescription('Get list of tubes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $tubes = $this->queue->tubes();
        if ($tubes->isEmpty()) {
            $output->writeln('There are no current tubes');

            return;
        }

        $output->writeln('Current tubes:');
        foreach ($tubes as $tube) {
            $output->writeln(' - ' . $tube->name());
        }
    }
}
