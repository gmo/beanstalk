<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Worker;

use Gmo\Beanstalk\Manager\WorkerInfo;
use GMO\Console\Helper\AutoHidingTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends AbstractWorkerCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('workers:stats')
            ->setDescription('Get stats about the workers')
            ->addOption('pids', 'p', null, 'Only return a list of PIDs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workers = $this->manager->getWorkers($input->getArgument('worker'));

        if ($this->input->getOption('pids')) {
            foreach ($workers as $worker) {
                $pids = $worker->getPids()->join("\n");
                if ($pids) {
                    $this->output->writeln($pids);
                }
            }

            return;
        }
        if ($workers->isEmpty()) {
            $this->output->writeln('There are no workers in: ' . $this->manager->getWorkerDir());

            return;
        }
        $this->output->writeln($this->renderStats($workers));
    }

    /**
     * @param WorkerInfo[]|iterable $workers
     *
     * @return string
     */
    protected function renderStats($workers)
    {
        $buffer = new BufferedOutput();
        $buffer->setDecorated(true);

        $width = $this->getConsoleWidth();
        $table = $width ? new AutoHidingTable($buffer, $width) : new Table($buffer);
        $table->setHeaders([
            'Worker',
            'Running',
            'Total',
            'PIDs',
        ]);
        foreach ($workers as $worker) {
            $table->addRow([
                $worker->getName(),
                $worker->getNumRunning(),
                $worker->getTotal(),
                $worker->getPids()->join(', '),
            ]);
        }
        $table->render();

        return $buffer->fetch();
    }
}
