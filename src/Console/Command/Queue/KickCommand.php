<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KickCommand extends ChangeStateCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('kick')
            ->setDescription('Kick buried and delayed jobs')
            ->addTubeArgument()
            ->addNumberOption()
        ;
    }

    protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output)
    {
        $number = (int) $input->getOption('number');
        $kicked = $tube->kick($number);
        $output->writeln("Kicked <info>$kicked</info> jobs in <info>$tube</info>");
    }
}
