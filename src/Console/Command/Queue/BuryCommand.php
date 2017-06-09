<?php

namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuryCommand extends ChangeStateCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('bury')
            ->setDescription('Bury jobs')
            ->addTubeArgument()
            ->addNumberOption()
        ;
    }

    protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output)
    {
        $number = intval($input->getOption('number'));

        $numberBuried = 0;
        do {
            $job = $tube->reserve(2);
            if ($job instanceof NullJob) {
                break;
            }
            $job->bury();
            $numberBuried++;
        } while (--$number !== 0);

        if ($numberBuried === 0) {
            return;
        }
        $output->writeln(
            "Buried <info>$numberBuried</info> job" . ($numberBuried > 1 ? 's' : '') . " in <info>$tube</info>"
        );
    }
}
