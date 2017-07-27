<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Bolt\Collection\ImmutableBag;
use Gmo\Beanstalk\Queue\Response\TubeStats;
use Gmo\Beanstalk\Tube\TubeCollection;
use GMO\Console\Helper\AutoHidingTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends AbstractQueueCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('stats')
            ->addArgument(
                'tube',
                InputArgument::IS_ARRAY,
                'The name of one or more tubes (does not have to be exact) <comment>(default: all tubes)</comment>'
            )
            ->addOption('refresh', 'f', InputOption::VALUE_NONE, 'Continue to refresh table every second')
            ->addOption('cron', null, InputOption::VALUE_NONE, 'Log stats from cron job')
            ->setDescription('Displays information about the current tubes')
            ->setHelp($this->getHelpText())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $refresh = $input->getOption('refresh');
        do {
            [$stats, $error] = $this->getStats($input, $output);
            if ($input->getOption('cron')) {
                $this->logStats($stats);

                return;
            }
            $output->writeln($this->renderStats($stats));
            sleep($refresh ? 1 : 0);
        } while ($refresh);

        if ($error) {
            $output->writeln('');
            $this->callCommand($output, ListCommand::NAME);
        }
    }

    private function getStats(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getQueue();
        if (!$tubes = $input->getArgument('tube')) {
            return [$queue->statsAllTubes(), false];
        }

        /** @var $tubes TubeCollection */
        [$tubes, $error] = $this->matchTubeNames($tubes, $output);
        $stats = [];
        foreach ($tubes as $name => $tube) {
            $stats[$name] = $tube->stats();
        }

        return [new ImmutableBag($stats), $error];
    }

    /**
     * @param TubeStats[]|ImmutableBag $stats
     * @param int|null                 $width
     *
     * @return string
     */
    private function renderStats(ImmutableBag $stats, $width = null)
    {
        if ($stats->isEmpty()) {
            return 'There are no current tubes';
        }

        $buffer = new BufferedOutput();
        $buffer->setDecorated(true);

        $width = $width ?: $this->getConsoleWidth();
        $table = $width ? new AutoHidingTable($buffer, $width) : new Table($buffer);
        $table->setHeaders([
            'Tube',
            'Ready',
            'Buried',
            'Reserved',
            'Delayed',
            'Urgent',
            'Total',
            '',
            'Using',
            'Watching',
            'Waiting',
            '',
            'Pause Elapsed',
            'Pause Left',
            '',
            'Delete Count',
            'Pause Count',
        ]);
        foreach ($stats as $tubeStats) {
            $table->addRow([
                $tubeStats->name(),
                $tubeStats->readyJobs(),
                $tubeStats->buriedJobs(),
                $tubeStats->reservedJobs(),
                $tubeStats->delayedJobs(),
                $tubeStats->urgentJobs(),
                $tubeStats->totalJobs(),
                '',
                $tubeStats->usingCount(),
                $tubeStats->watchingCount(),
                $tubeStats->waitingCount(),
                '',
                $tubeStats->pause(),
                $tubeStats->pauseTimeLeft(),
                '',
                $tubeStats->cmdDeleteCount(),
                $tubeStats->cmdPauseTubeCount(),
            ]);
        }
        $table->render();

        return $buffer->fetch();
    }

    /**
     * @param TubeStats[]|ImmutableBag $tubes
     */
    private function logStats(ImmutableBag $tubes)
    {
        $logger = $this->getService('beanstalk.queue.logger');
        foreach ($tubes as $tube => $stats) {
            $logger->info('Tube stats', [
                'tube'          => $tube,
                'ready'         => $stats->readyJobs(),
                'buried'        => $stats->buriedJobs(),
                'delayed'       => $stats->delayedJobs(),
                'total'         => $stats->readyJobs() + $stats->buriedJobs() + $stats->delayedJobs(),
                'pause_left'    => $stats->pauseTimeLeft(),
                'pause_elapsed' => $stats->pause(),
                'workers'       => $stats->watchingCount(),
                'deleted_count' => $stats->cmdDeleteCount(),
                'pause_count'   => $stats->cmdPauseTubeCount(),
            ]);
        }
    }

    private function getHelpText()
    {
        $exampleStats = new ImmutableBag([
            new TubeStats(['name' => 'TubeA']),
            new TubeStats(['name' => 'SendApi']),
            new TubeStats(['name' => 'ReceiveApi']),
        ]);
        $stats = preg_replace("#\n#", "\n      ", $this->renderStats($exampleStats, 78));

        return <<<EOF

The <info>%command.name%</info> command displays information about the current tubes

<comment>Example command:</comment>
      <info>php %command.full_name% tubeA api</info>

   will output:
      {$stats}
   Notice case sensitivity doesn't matter and both the <info>SendApi</info> and <info>ReceiveApi</info> tubes are matched.

<comment>Header Explanation:</comment>
   Tube             The tube's name
   Ready            The number of ready jobs
   Buried           The number of buried jobs
   Reserved         The number of jobs reserved by all clients
   Delayed          The number of delayed jobs
   Urgent           The number of ready jobs with priority < 1024
   Total            The cumulative count of jobs created for the tube in
                      the current beanstalkd process

   Using            The number of open connections that are currently using the tube
   Watching         The number of open connections that are currently watching the tube
   Waiting          The number of open connections that have issued a reserve command
                      while watching the tube but not yet received a response

   Pause Elapsed    The number of seconds the tube has been paused for
   Pause Left       The number of seconds until the tube is un-paused

   Delete Count     The cumulative number of delete commands for the tube
   Pause Count      The cumulative number of pause commands for the tube

EOF;
    }
}
