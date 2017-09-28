<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Console\Command\AbstractCommand;
use Gmo\Beanstalk\Queue\QueueInterface;
use Gmo\Beanstalk\Tube\Tube;
use Gmo\Beanstalk\Tube\TubeCollection;
use Gmo\Common\Str;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractQueueCommand extends AbstractCommand
{
    /**
     * @return QueueInterface
     */
    protected function getQueue()
    {
        return $this->getService('beanstalk.queue');
    }

    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        if ($argumentName === 'tube') {
            return $this->completeTubeNames($context);
        }

        return parent::completeArgumentValues($argumentName, $context);
    }

    protected function matchTubeNames($tubesSearch, OutputInterface $output)
    {
        $matchedTubes = new TubeCollection();
        $queue = $this->getQueue();
        $error = false;
        foreach ((array) $tubesSearch as $tubeSearch) {
            $matched = $queue
                ->tubes()
                ->filter(function ($i, Tube $tube) use ($tubeSearch) {
                    return Str::contains($tube->name(), $tubeSearch, false);
                })
            ;
            if ($matched->isEmpty()) {
                $output->writeln("<warn>No tubes matched to: $tubeSearch</warn>");
                $error = true;
            }
            $matchedTubes = $matchedTubes->replace($matched);
        }

        return [$matchedTubes, $error];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->setupQueue($input);
    }

    private function setupQueue(InputInterface $input)
    {
        $container = $this->getContainer();
        if ($input->hasOption('host') && $host = $input->getOption('host')) {
            $container['beanstalk.host'] = $host;
        }
        if ($input->hasOption('port') && $port = $input->getOption('port')) {
            $container['beanstalk.port'] = $port;
        }

        $logger = $this->logger;
        if ($container instanceof \Pimple) {
            $container['beanstalk.queue'] = $container->share($container->extend('beanstalk.queue', function (QueueInterface $queue) use ($logger) {
                $queue->setLogger($logger);

                return $queue;
            }));
        } elseif ($container instanceof \Pimple\Container) {
            $container->extend('beanstalk.queue', function (QueueInterface $queue) use ($logger) {
                $queue->setLogger($logger);

                return $queue;
            });
        } else {
            $queue = $container['beanstalk.queue'];
            $queue->setLogger($logger);
            $container['beanstalk.queue'] = $queue;
        }
    }

    private function completeTubeNames(CompletionContext $context)
    {
        $input = $this->createInputFromContext($context);

        $currentTubes = array_map('strtolower', $input->getArgument('tube'));
        $currentWord = $context->getCurrentWord();

        if (empty($currentWord)) {
            $tubes = $this->getQueue()->tubes();
        } else {
            [$tubes, $error] = $this->matchTubeNames($currentWord, new NullOutput());
        }

        return $tubes
            ->keys()
            ->filter(function ($i, $name) use ($currentTubes) {
                // filter out tubes already defined in input
                return !in_array(strtolower($name), $currentTubes, true);
            })
            ->map(function ($i, $name) use ($currentWord) {
                // change case to match current word, else it will be filtered out
                return $currentWord . substr($name, strlen($currentWord));
            })
            ->toArray()
        ;
    }
}
