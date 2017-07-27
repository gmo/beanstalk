<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Job\Job;
use Gmo\Beanstalk\Job\NullJob;
use Gmo\Beanstalk\Tube\Tube;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PeekCommand extends ChangeStateCommand
{
    protected $ids = array();

    protected function configure()
    {
        parent::configure();
        $this->setName('peek')
            ->setDescription('Peek at the first job')
            ->addTubeArgument()
            ->addStateOptions()
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Inspect job by ID')
            ->addOption('stats', 's', InputOption::VALUE_NONE, 'Also output the job\'s stats')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ids = array();
        if ($id = $input->getOption('id')) {
            $this->ids[] = $id;
            $this->validateState(false);
            $this->validateTube(false);
        }

        parent::execute($input, $output);

        if ($id) {
            $job = $this->getQueue()->peekJob(intval($id));
            $output->writeln("Peeking at job <info>#{$job->getId()}</info>");
            $output->writeln($this->renderJobData($job));
        }

        if ($input->getOption('stats')) {
            $this->renderStats($output, $this->ids);
        }
    }

    protected function forEachTube(Tube $tube, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('ready')) {
            $job = $tube->peekReady();
            $this->outputJob($output, $job, $tube, 'ready');
        }
        if ($input->getOption('buried')) {
            $job = $tube->peekBuried();
            $this->outputJob($output, $job, $tube, 'buried');
        }
        if ($input->getOption('delayed')) {
            $job = $tube->peekDelayed();
            $this->outputJob($output, $job, $tube, 'delayed');
        }
    }

    protected function outputJob(OutputInterface $output, Job $job, Tube $tube, $state)
    {
        if ($job instanceof NullJob) {
            $output->writeln("There are no $state jobs in <info>$tube</info> tube");

            return;
        }
        $this->ids[] = $job->getId();
        $output->writeln("Peeking at the $state job <info>#{$job->getId()}</info> in <info>$tube</info> tube");
        $output->writeln($this->renderJobData($job));
    }

    protected function renderJobData(Job $job)
    {
        $data = $job->getData();
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }

        return $this->dumpVar($data);
    }

    protected function renderStats(OutputInterface $output, array $ids)
    {
        $this->callCommand($output, JobStatsCommand::NAME, array('id' => $ids));
    }
}
