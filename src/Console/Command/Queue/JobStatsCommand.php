<?php

namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Common\Collections\ArrayCollection;
use GMO\Console\Helper\AutoHidingTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class JobStatsCommand extends AbstractQueueCommand
{
    const NAME = 'job-stats';

    protected function configure()
    {
        parent::configure();
        $this->setName(static::NAME)
            ->addArgument('id', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The ID(s) of the job')
            ->setDescription('Displays information about a job')
            ->setHelp($this->getHelpText())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $queue = $this->getQueue();
        $stats = new ArrayCollection();
        foreach ($input->getArgument('id') as $id) {
            $jobStats = $queue->statsJob($id);
            if ($jobStats->id() !== -1) {
                $stats->add($jobStats);
            } else {
                $output->writeln("Job <info>#$id</info> does not exist");
            }
        }
        if (!$stats->isEmpty()) {
            $output->writeln($this->renderStats($stats));
        }
    }

    /**
     * @param JobStats[]|ArrayCollection $statsList
     *
     * @return string
     */
    private function renderStats($statsList)
    {
        $buffer = new BufferedOutput();
        $buffer->setDecorated(true);

        $width = $this->getConsoleWidth();
        $table = $width ? new AutoHidingTable($buffer, $width) : new Table($buffer);
        $table->setHeaders(array(
            'ID',
            'Tube',
            'State',
            '# Reserves',
            '# Releases',
            '# Buries',
            '# Kicks',
            'Priority',
            'Time left (secs)',
            '# Timeouts',
            'Age (secs)',
            'File',
        ));
        foreach ($statsList as $stats) {
            $table->addRow(array(
                $stats->id(),
                $stats->tube(),
                $stats->state(),
                $stats->reserves(),
                $stats->releases(),
                $stats->buries(),
                $stats->kicks(),
                $stats->priority(),
                in_array($stats->state(), array('reserved', 'delayed')) ? $stats->timeLeft() : 'N/A',
                $stats->timeouts(),
                $stats->age(),
                $stats->file() === 0 ? 'N/A' : $stats->file(),
            ));
        }

        $table->render();

        return $buffer->fetch();
    }

    private function getHelpText()
    {
        return <<<EOF
<comment>Header Explanation:</comment>
   <info>ID</info>          The job's ID
   <info>Tube</info>        The tube the job is located
   <info>State</info>       The current state of the job. Either <comment>ready</comment>, <comment>reserved</comment>, <comment>buried</comment>, or <comment>delayed</comment>.
   <info>Reserves</info>    The number of times the job has been reserved
   <info>Buries</info>      The number of times the job has been buried
   <info>Kicks</info>       The number of times the job has been kicked
   <info>Priority</info>    The priority of the job
   <info>Time left</info>   The number of seconds left until the server puts this job into the ready state.
                 This is only meaningful if the job is reserved or delayed.
   <info>Timeouts</info>    The number of times the job has timed out during a reservation
   <info>Age</info>         The time in seconds since the job was created
   <info>File</info>        The number of the earliest binlog file containing the job.
                 Only applicable if binlogs are enabled.

EOF;
    }
}
