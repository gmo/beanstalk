<?php

namespace GMO\Beanstalk\Console\Command\Worker;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Manager\WorkerInfo;
use GMO\Beanstalk\Manager\WorkerManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractWorkerCommand extends AbstractCommand
{
    public function setName($name)
    {
        return parent::setName("workers:$name");
    }

    protected function configure()
    {
        parent::configure();
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $manager = $this->getManager($input);
        if (!$manager->getWorkerDir()) {
            throw new \RuntimeException(
                'Worker directory needs to be passed in via --dir or set in the dependency container'
            );
        }
        $this->executeManagerFunction($input, $output, $manager, $input->getArgument('worker'));
    }

    protected function executeManagerFunction(InputInterface $input, OutputInterface $output, WorkerManager $manager, $workers)
    {
    }

    protected function getManager(InputInterface $input)
    {
        $container = $this->getContainer();
        if ($input->hasOption('host') && $host = $input->getOption('host')) {
            $container['beanstalk.host'] = $host;
        }
        if ($input->hasOption('port') && $port = $input->getOption('port')) {
            $container['beanstalk.port'] = $port;
        }
        if ($input->hasOption('dir') && $dir = $input->getOption('dir')) {
            $container['beanstalk.worker_manager.directory'] = $dir;
        }

        /** @var WorkerManager $manager */
        $manager = $container['beanstalk.worker_manager'];
        $manager->setLogger($this->logger);

        return $manager;
    }

    private function completeWorkerNames(CompletionContext $context)
    {
        try {
            $input = $this->createInputFromContext($context);
            $manager = $this->getManager($input);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        $currentWorkers = array_map('strtolower', $input->getArgument('worker'));
        $currentWord = $context->getCurrentWord();

        return $manager
            ->getWorkers($currentWord)
            ->filter(function (WorkerInfo $info) use ($currentWorkers) {
                // filter out workers already defined in input
                return !in_array(strtolower($info->getName()), $currentWorkers);
            })
            ->map(function (WorkerInfo $info) use ($currentWord) {
                // change case to match current word, else it will be filtered out
                return $currentWord . substr($info->getName(), strlen($currentWord));
            })
            ->getValues()
            ->toArray()
        ;
    }
}
