<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Common\Collections\ArrayCollection;
use GMO\Console\Helper\AutoHidingTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends AbstractQueueCommand {

	protected function configure() {
		$this->setName('stats')
			->addArgument(
				'tube',
				InputArgument::IS_ARRAY,
				'The name of one or more tubes (does not have to be exact) <comment>(default: all tubes)</comment>')
			->addOption('refresh', 'f', InputOption::VALUE_NONE, 'Continue to refresh table every second')
			->setDescription('Displays information about the current tubes')
			->setHelp($this->getHelpText());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);

		$refresh = $input->getOption('refresh');
		do {
			list($stats, $error) = $this->getStats($input, $output);
			$output->writeln($this->renderStats($stats));
			sleep($refresh ? 1 : 0);
		} while($refresh);

		if ($error) {
			$output->writeln('');
			$this->callCommand($output, ListCommand::NAME);
		}
	}

	private function getStats(InputInterface $input, OutputInterface $output) {
		$queue = $this->getQueue();
		if (!$tubes = $input->getArgument('tube')) {
			$stats = $queue->statsAllTubes();
			$error = false;
		} else {
			list($tubes, $error) = $this->matchTubeNames($tubes, $output);
			$stats = new ArrayCollection();
			foreach ($tubes as $tube) {
				$stats->set($tube, $queue->statsTube($tube));
			}
		}
		return array($stats, $error);
	}

	/**
	 * @param TubeStats[]|ArrayCollection $stats
	 * @param bool                        $full
	 * @return string
	 */
	private function renderStats(ArrayCollection $stats, $full = true) {
		if ($stats->isEmpty()) {
			return '';
		}

		$buffer = new BufferedOutput();
		$buffer->setDecorated(true);

		$width = $this->getConsoleWidth();
		$table = $width ? new AutoHidingTable($buffer, $width) : new Table($buffer);
		$headers = array(
			'Tube',
			'Ready',
			'Buried',
			'Reserved',
			'Delayed',
			'Urgent',
			'Total',
		);
		if ($full) {
			$headers = array_merge($headers, array(
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
			));
		}
		$table->setHeaders($headers);
		foreach ($stats as $tubeStats) {
			$row = array(
				$tubeStats->name(),
				$tubeStats->readyJobs(),
				$tubeStats->buriedJobs(),
				$tubeStats->reservedJobs(),
				$tubeStats->delayedJobs(),
				$tubeStats->urgentJobs(),
				$tubeStats->totalJobs(),
			);
			if ($full) {
				$row = array_merge($row, array(
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
				));
			}
			$table->addRow($row);
		}
		$table->render();

		return $buffer->fetch();
	}

	private function getHelpText() {
		$exampleStats = ArrayCollection::create(array(
			new TubeStats(array('name' => 'TubeA')),
			new TubeStats(array('name' => 'SendApi')),
			new TubeStats(array('name' => 'ReceiveApi')),
		));
		$stats = preg_replace("#\n#", "\n      ", $this->renderStats($exampleStats, false));

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
