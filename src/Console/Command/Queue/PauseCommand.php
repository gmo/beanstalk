<?php

namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PauseCommand extends ChangeStateCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('pause')
            ->setDescription('Pause tubes')
            ->addArgument('delay', InputArgument::OPTIONAL, 'Pause the tube(s) for this many seconds', 0)
            ->addTubeArgument()
        ;
    }

    protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output)
    {
        $delay = intval($input->getArgument('delay'));
        $tube->pause($delay);
    }
}
