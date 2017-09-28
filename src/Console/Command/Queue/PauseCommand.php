<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PauseCommand extends ChangeStateCommand
{
    protected function configure()
    {
        $this->setName('queue:pause')
            ->setDescription('Pause tubes')
            ->addArgument('delay', InputArgument::OPTIONAL, 'Pause the tube(s) for this many seconds', 0)
            ->addTubeArgument()
        ;
    }

    protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output)
    {
        $delay = (int) $input->getArgument('delay');
        $tube->pause($delay);
    }
}
