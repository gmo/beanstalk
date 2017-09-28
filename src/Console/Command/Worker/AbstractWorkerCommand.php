<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Worker;

use Gmo\Beanstalk\Console\Command\AbstractCommand;
use Gmo\Beanstalk\Manager\WorkerInfo;
use Gmo\Beanstalk\Manager\WorkerManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractWorkerCommand extends AbstractCommand
{
    /** @var WorkerManager */
    protected $manager;

    protected function configure()
    {
        $this->addArgument(
            'worker',
            InputArgument::IS_ARRAY,
            'The name of the worker (does not have to be exact) <comment>(default: all workers)</comment>'
        );
    }

    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        if ($argumentName === 'worker') {
            return $this->completeWorkerNames($context);
        }

        return parent::completeArgumentValues($argumentName, $context);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->manager = $this->getOrCreate('beanstalk.worker_manager', function () {
            $dir = $this->input->hasOption('dir') ? $this->input->getOption('dir') : null;

            if (!$dir) {
                throw new \RuntimeException(
                    'Worker directory needs to be passed in via --dir or set in the dependency container'
                );
            }

            return new WorkerManager(
                $dir,
                $this->logger,
                $this->host,
                $this->port
            );
        });
    }

    private function completeWorkerNames(CompletionContext $context)
    {
        try {
            $this->initializeFromContext($context);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        $currentWorkers = array_map('strtolower', $this->input->getArgument('worker'));
        $currentWord = $context->getCurrentWord();

        return $this->manager
            ->getWorkers($currentWord)
            ->filter(function ($i, WorkerInfo $info) use ($currentWorkers) {
                // filter out workers already defined in input
                return !in_array(strtolower($info->getName()), $currentWorkers, true);
            })
            ->map(function ($i, WorkerInfo $info) use ($currentWord) {
                // change case to match current word, else it will be filtered out
                return $currentWord . substr($info->getName(), strlen($currentWord));
            })
            ->values()
            ->toArray()
        ;
    }
}
