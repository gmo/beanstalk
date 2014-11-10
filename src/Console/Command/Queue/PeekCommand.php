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
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!$id = $input->getOption('id')) {
			parent::execute($input, $output);
			return;
		}

		$this->validateState(false);
		$this->validateTube(false);

		parent::execute($input, $output);

		$job = $this->getQueue($input)->peekJob(intval($id));
		$output->writeln("Peeking at job <info>#{$job->getId()}</info>");
		$output->writeln(print_r($job->getData(), true));
	}

	protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output) {
		if ($input->getOption('ready')) {
			$job = $queue->peekReady($tube);
			$this->outputJob($output, $job, $tube, 'ready');
		}
		if ($input->getOption('buried')) {
			$job = $queue->peekBuried($tube);
			$this->outputJob($output, $job, $tube, 'buried');
		}
		if ($input->getOption('delayed')) {
			$job = $queue->peekDelayed($tube);
			$this->outputJob($output, $job, $tube, 'delayed');
		}
	}

	protected function outputJob(OutputInterface $output, Job $job, $tube, $state) {
		$output->writeln("Peeking at the $state job <info>#{$job->getId()}</info> in <info>$tube</info> tube");
		$output->writeln(print_r($job->getData(), true));
	}
}
