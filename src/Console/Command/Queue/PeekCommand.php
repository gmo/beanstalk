<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Queue;
use GMO\Beanstalk\Queue\QueueInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PeekCommand extends ChangeStateCommand {

	protected function configure() {
		parent::configure();
		$this->setName('peek')
			->setDescription('Peek at the first job')
			->addTubeArgument()
			->addStateOptions()
			->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Inspect job by ID')
			->addOption('stats', 's', InputOption::VALUE_NONE, 'Also output the job\'s stats')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->ids = array();
		if ($id = $input->getOption('id')) {
			$this->ids[] = $id;
			$this->validateState(false);
			$this->validateTube(false);
		}

		parent::execute($input, $output);

		if ($id) {
			$job = $this->getQueue($input)->peekJob(intval($id));
			$output->writeln("Peeking at job <info>#{$job->getId()}</info>");
			$output->writeln(print_r($job->getData(), true));
		}

		if ($input->getOption('stats')) {
			$this->renderStats($output, $this->ids);
		}
	}

	protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output) {
		if ($input->getOption('ready')) {
			$job = $queue->peekReady($tube);
			$this->ids[] = $job->getId();
			$this->outputJob($output, $job, $tube, 'ready');
		}
		if ($input->getOption('buried')) {
			$job = $queue->peekBuried($tube);
			$this->ids[] = $job->getId();
			$this->outputJob($output, $job, $tube, 'buried');
		}
		if ($input->getOption('delayed')) {
			$job = $queue->peekDelayed($tube);
			$this->ids[] = $job->getId();
			$this->outputJob($output, $job, $tube, 'delayed');
		}
	}

	protected function outputJob(OutputInterface $output, Job $job, $tube, $state) {
		$output->writeln("Peeking at the $state job <info>#{$job->getId()}</info> in <info>$tube</info> tube");
		$output->writeln(print_r($job->getData(), true));
	}

	protected function renderStats(OutputInterface $output, array $ids) {
		$this->callCommand($output, JobStatsCommand::NAME, array('id' => $ids));
	}

	protected $ids = array();
}
