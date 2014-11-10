<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use GMO\Beanstalk\Queue\QueueInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends ChangeStateCommand {

	protected function configure() {
		parent::configure();
		$this->setName('delete')
			->setDescription('Delete jobs')
			->addTubeArgument()
			->addStateOptions()
			->addNumberOption()
		;
	}

	protected function forEachTube(QueueInterface $queue, $tube, InputInterface $input, OutputInterface $output) {
		$number = intval($input->getOption('number'));
		if ($input->getOption('ready')) {
			$num = $queue->deleteReadyJobs($tube, $number);
			$output->writeln("Deleted <info>$num ready</info> jobs in <info>$tube</info>");
		}
		if ($input->getOption('buried')) {
			$num = $queue->deleteBuriedJobs($tube, $number);
			$output->writeln("Deleted <info>$num buried</info> jobs in <info>$tube</info>");
		}
		if ($input->getOption('delayed')) {
			$num = $queue->deleteDelayedJobs($tube, $number);
			$output->writeln("Deleted <info>$num delayed</info> jobs in <info>$tube</info>");
		}
	}
}
