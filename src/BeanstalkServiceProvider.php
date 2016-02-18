<?php
namespace GMO\Beanstalk;

use GMO\Beanstalk\Console\Command;
use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Beanstalk\Queue\Queue;
use GMO\Beanstalk\Queue\WebJobProducer;
use GMO\DependencyInjection\ServiceProviderInterface;
use Pimple;
use Psr\Log\NullLogger;

/**
 * BeanstalkServiceProvider for dependency injection
 * @package GMO\Beanstalk
 * @since 1.7.0
 */
class BeanstalkServiceProvider implements ServiceProviderInterface {

	/** @inheritdoc */
	public function register(Pimple $container) {

		$container[BeanstalkKeys::HOST] = 'localhost';
		$container[BeanstalkKeys::PORT] = 11300;

		$container[BeanstalkKeys::WORKER_DIRECTORY] = null;
		$container[BeanstalkKeys::QUEUE_LOGGER] =
		$container[BeanstalkKeys::WORKER_MANAGER_LOGGER] = $container->share(function($app) {
			if (isset($app['logger.new'])) {
				return $app['logger.new']('Queue');
			} elseif (isset($app['logger'])) {
				return $app['logger'];
			} else {
				return new NullLogger();
			}
		});

		$container[BeanstalkKeys::QUEUE] = $container->share(function($app) {
			return new Queue(
				$app[BeanstalkKeys::HOST],
				$app[BeanstalkKeys::PORT],
				$app[BeanstalkKeys::QUEUE_LOGGER]
			);
		});

		$container[BeanstalkKeys::WEB_JOB_PRODUCER] = $container->share(function($app) {
			return new WebJobProducer(
				$app[BeanstalkKeys::QUEUE],
				$app[BeanstalkKeys::QUEUE_LOGGER]
			);
		});

		$container[BeanstalkKeys::WORKER_MANAGER] = $container->share(function($app) {
			return new WorkerManager(
				$app[BeanstalkKeys::WORKER_DIRECTORY],
				$app[BeanstalkKeys::WORKER_MANAGER_LOGGER],
				$app[BeanstalkKeys::HOST],
				$app[BeanstalkKeys::PORT]
			);
		});

		$container['beanstalk.console_commands.queue_prefix'] = 'queue';
		$container['beanstalk.console_commands'] = $container->share(function ($app) {
			$prefix = $app['beanstalk.console_commands.queue_prefix'];
			return array(
				new Command\Queue\ListCommand($prefix),
				new Command\Queue\KickCommand($prefix),
				new Command\Queue\DeleteCommand($prefix),
				new Command\Queue\BuryCommand($prefix),
				new Command\Queue\PeekCommand($prefix),
				new Command\Queue\PauseCommand($prefix),
				new Command\Queue\StatsCommand($prefix),
				new Command\Queue\ServerStatsCommand($prefix),
				new Command\Queue\JobStatsCommand($prefix),
				new Command\Worker\StartCommand(),
				new Command\Worker\StopCommand(),
				new Command\Worker\RestartCommand(),
				new Command\Worker\StatsCommand(),
			);
		});

		$container['beanstalk.console_commands.auto_add'] = true;
		if (isset($container['console'])) {
			$container['console'] = $container->share($container->extend('console', function ($console, $app) {
				if (!$app['beanstalk.console_commands.auto_add'] || !$console instanceof \Symfony\Component\Console\Application) {
					return $console;
				}
				$console->addCommands($app['beanstalk.console_commands']);

				return $console;
			}));
		}
	}

	/** @inheritdoc */
	public function boot(Pimple $container) { }
}
