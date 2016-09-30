<?php
namespace GMO\Beanstalk\Console\Command\Queue;

use GMO\Beanstalk\BeanstalkKeys;
use GMO\Beanstalk\Console\Command\AbstractCommand;
use GMO\Beanstalk\Queue\QueueInterface;
use GMO\Beanstalk\Tube\Tube;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Str;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractQueueCommand extends AbstractCommand {

	protected $namePrefix;

	/**
	 * Constructor.
	 *
	 * @param $namePrefix
	 */
	public function __construct($namePrefix = 'queue')
	{
		$this->namePrefix = $namePrefix ? rtrim($namePrefix, ':') . ':' : '';
		parent::__construct();
	}

	public function setName($name)
	{
		return parent::setName($this->namePrefix . $name);
	}

	/**
	 * @return QueueInterface
	 */
	protected function getQueue() {
		return $this->getService(BeanstalkKeys::QUEUE);
	}

	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'tube') {
			return $this->completeTubeNames($context);
		}

		return parent::completeArgumentValues($argumentName, $context);
	}

	protected function matchTubeNames($tubesSearch, OutputInterface $output) {
		$matchedTubes = new ArrayCollection();
		$queue = $this->getQueue();
		$error = false;
		foreach ((array) $tubesSearch as $tubeSearch) {
			$matched = $queue
				->tubes()
				->filter(function(Tube $tube) use ($tubeSearch) {
					return Str::contains($tube->name(), $tubeSearch, false);
				});
			if ($matched->isEmpty()) {
				$output->writeln("<warn>No tubes matched to: $tubeSearch</warn>");
				$error = true;
			}
			$matchedTubes->merge($matched);
		}
		return array($matchedTubes, $error);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		parent::execute($input, $output);
		$this->setupQueue($input);
	}

	private function setupQueue(InputInterface $input) {
		$container = $this->getContainer();
		if ($input->hasOption('host') && $host = $input->getOption('host')) {
			$container[BeanstalkKeys::HOST] = $host;
		}
		if ($input->hasOption('port') && $port = $input->getOption('port')) {
			$container[BeanstalkKeys::PORT] = $port;
		}

		$logger = $this->logger;
		if ($container instanceof \Pimple) {
            $container[BeanstalkKeys::QUEUE] = $container->share($container->extend(BeanstalkKeys::QUEUE, function (QueueInterface $queue) use ($logger) {
                $queue->setLogger($logger);
                return $queue;
            }));
        } elseif ($container instanceof \Pimple\Container) {
            $container->extend(BeanstalkKeys::QUEUE, function (QueueInterface $queue) use ($logger) {
                $queue->setLogger($logger);
                return $queue;
            });
        } else {
            $queue = $container[BeanstalkKeys::QUEUE];
            $queue->setLogger($logger);
            $container[BeanstalkKeys::QUEUE] = $queue;
        }
	}

	private function completeTubeNames(CompletionContext $context) {
		$input = $this->createInputFromContext($context);

		$currentTubes = array_map('strtolower', $input->getArgument('tube'));
		$currentWord = $context->getCurrentWord();

		if (empty($currentWord)) {
			$tubes = $this->getQueue()->tubes();
		} else {
			list($tubes, $error) = $this->matchTubeNames($currentWord, new NullOutput());
		}

		return $tubes
			->getKeys()
			->filter(function($name) use ($currentTubes) {
				// filter out tubes already defined in input
				return !in_array(strtolower($name), $currentTubes);
			})
			->map(function ($name) use ($currentWord) {
				// change case to match current word, else it will be filtered out
				return $currentWord . substr($name, strlen($currentWord));
			})
			->toArray()
		;
	}
}
