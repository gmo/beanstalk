<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Job\NullJob;
use Gmo\Beanstalk\Tube\Tube;
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
