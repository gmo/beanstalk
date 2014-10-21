<?php
namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\Manager\WorkerInfo;
use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Common\Collections\ArrayCollection;
use GMO\Console\Helper\AutoHidingTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends AbstractWorkerCommand {

	protected function configure() {
		parent::configure();
		$this->setName('stats')->setDescription('Get stats about the workers');
	}

	protected function executeManagerFunction(OutputInterface $output, WorkerManager $manager, $workers) {
		$output->writeln($this->renderStats($manager->getWorkers($workers)));
	}

	/**
	 * @param WorkerInfo[]|ArrayCollection $workers
	 * @return string
	 */
	protected function renderStats($workers) {
		$buffer = new BufferedOutput();
		$buffer->setDecorated(true);

		$width = $this->getConsoleWidth();
		$table = $width ? new AutoHidingTable($buffer, $width) : new Table($buffer);
		$table->setHeaders(array(
			'Worker',
			'Running',
			'Total',
			'PIDs'
		));
		foreach ($workers as $worker) {
			$table->addRow(array(
				$worker->getName(),
				$worker->getNumRunning(),
				$worker->getTotal(),
				$worker->getPids()->join(', '),
			));
		}
		$table->render();

		return $buffer->fetch();
	}
}
