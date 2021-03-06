<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends ChangeStateCommand
{
    protected function configure()
    {
        $this->setName('queue:delete')
            ->setDescription('Delete jobs')
            ->addTubeArgument()
            ->addStateOptions()
            ->addNumberOption()
        ;
    }

    protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output)
    {
        $number = (int) $input->getOption('number');
        if ($input->getOption('ready')) {
            $num = $tube->deleteReadyJobs($number);
            $output->writeln("Deleted <info>$num ready</info> jobs in <info>$tube</info>");
        }
        if ($input->getOption('buried')) {
            $num = $tube->deleteBuriedJobs($number);
            $output->writeln("Deleted <info>$num buried</info> jobs in <info>$tube</info>");
        }
        if ($input->getOption('delayed')) {
            $num = $tube->deleteDelayedJobs($number);
            $output->writeln("Deleted <info>$num delayed</info> jobs in <info>$tube</info>");
        }
    }
}
