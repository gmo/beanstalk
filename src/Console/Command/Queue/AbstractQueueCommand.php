<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Console\Command\Queue;

use Gmo\Beanstalk\Console\Command\AbstractCommand;
use Gmo\Beanstalk\Queue\Queue;
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
    /** @var QueueInterface */
    protected $queue;

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
        $error = false;
        foreach ((array) $tubesSearch as $tubeSearch) {
            $matched = $this->queue
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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->queue = $this->getOrCreate('beanstalk.queue', function () {
            return new Queue(
                $this->host,
                $this->port,
                $this->logger
            );
        });
    }

    private function completeTubeNames(CompletionContext $context)
    {
        try {
            $this->initializeFromContext($context);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        $currentTubes = array_map('strtolower', $this->input->getArgument('tube'));
        $currentWord = $context->getCurrentWord();

        if (empty($currentWord)) {
            $tubes = $this->queue->tubes();
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
