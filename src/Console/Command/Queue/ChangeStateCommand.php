<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ChangeStateCommand extends AbstractQueueCommand
{
    private $validateState = false;
    private $validateTube = false;

    abstract protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output);

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($this->validateState) {
            if (!$input->getOption('ready') && !$input->getOption('buried') && !$input->getOption('delayed')) {
                throw new \RuntimeException('One or more states must be specified. (ready, buried, and/or delayed)');
            }
        }
        if ($this->validateTube) {
            if (!$input->getOption('all') && !$input->getArgument('tube')) {
                throw new \RuntimeException('One or more tubes must be specified.');
            }
        }

        $queue = $this->getQueue();
        if ($input->getOption('all')) {
            $error = false;
            $tubes = $queue->tubes();
            if ($tubes->isEmpty()) {
                $output->writeln('There are no current tubes');
            }
        } else {
            [$tubes, $error] = $this->matchTubeNames($input->getArgument('tube'), $output);
        }
        foreach ($tubes as $tube) {
            $this->forEachTube($tube, $input, $output);
        }

        if ($error) {
            $output->writeln('');
            $this->callCommand($output, 'queue:tubes');
        }
    }

    protected function addTubeArgument()
    {
        $this->validateTube();

        return $this
            ->addArgument('tube', InputArgument::IS_ARRAY, 'The name of the tube(s) (does not have to be exact)')
            ->addOption('all', 'a', InputOption::VALUE_NONE, ucfirst($this->getName()) . ' jobs in ALL tubes')
        ;
    }

    protected function addStateOptions()
    {
        $this->validateState();
        $name = ucfirst($this->getName());

        return $this
            ->addOption('ready', 'r', InputOption::VALUE_NONE, "$name jobs in ready state")
            ->addOption('buried', 'b', InputOption::VALUE_NONE, "$name jobs in buried state")
            ->addOption('delayed', 'd', InputOption::VALUE_NONE, "$name jobs in delayed state")
        ;
    }

    protected function addNumberOption()
    {
        return $this->addOption(
            'number',
            'm',
            InputOption::VALUE_REQUIRED,
            'Number of jobs to ' . $this->getName(),
            -1
        );
    }

    protected function validateState($enable = true)
    {
        $this->validateState = $enable;

        return $this;
    }

    protected function validateTube($enable = true)
    {
        $this->validateTube = $enable;

        return $this;
    }
}
