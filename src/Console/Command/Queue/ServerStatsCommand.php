<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\Queue;
use GMO\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStatsCommand extends AbstractQueueCommand {

	protected function configure() {
		parent::configure();
		$this->setName('server-stats')
			->setDescription('Displays information about the beanstalkd server')
			->addOption('list', 	'l', InputOption::VALUE_NONE, 'List all the stat names')
			->addOption('stat', 's',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'Output the value of the specified stat (one per line)')
			->addOption('current', 	'c', InputOption::VALUE_NONE, 'List current count stats')
			->addOption('cmd', 	   null, InputOption::VALUE_NONE, 'List command count stats')
			->addOption('other', 	'o', InputOption::VALUE_NONE, 'List other stats and values')
			->addOption('binlog', 	'b', InputOption::VALUE_NONE, 'List binlog stats and values')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);

		$stats = $this->getQueue($input)->statsServer();

		if ($input->getOption('list')) {
			foreach ($stats->getKeys() as $key) {
				$output->writeln($key);
			}
			return;
		}

		if ($input->getOption('stat')) {
			foreach ($input->getOption('stat') as $statName) {
				if (!$stats->containsKey($statName)) {
					$output->writeln("<error>Stat: \"$statName\" does not exist</error>");
					continue;
				}
				$output->writeln($stats->get($statName, 0));
			}
			return;
		}

		$optionNames = ArrayCollection::create(array('current', 'cmd', 'binlog', 'other'));
		if ($optionNames->forAll(function($key, $value) use ($input) {
			return !$input->getOption($value);
		})) {
			$input->setOption('current', true);
			$input->setOption('cmd', true);
			$input->setOption('binlog', true);
			$input->setOption('other', true);
		}

		foreach ($optionNames as $opt) {
			if (!$input->getOption($opt)) {
				continue;
			}
			$this->renderStats($output, $opt, $stats->{"get{$opt}Stats"}());
		}
	}

	private function renderStats(OutputInterface $output, $name, ArrayCollection $stats) {
		$output->writeln("<comment>" . ucfirst($name) . " Stats:</comment>");
		foreach ($stats as $statName => $value) {
			$output->writeln("<info>$statName</info>: $value");
		}
		$output->writeln('');
	}
}
