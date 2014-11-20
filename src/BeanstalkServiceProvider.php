<?php
namespace GMO\Beanstalk;

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
		$container[BeanstalkKeys::WORKER_MANAGER_LOGGER] = function() {
			return new NullLogger();
		};

		$container[BeanstalkKeys::QUEUE] = $container->share(function($app) {
			return new Queue(
				$app[BeanstalkKeys::HOST],
				$app[BeanstalkKeys::PORT],
				$app[BeanstalkKeys::QUEUE_LOGGER]
			);
		});

		$container[BeanstalkKeys::WEB_JOB_PRODUCER] = $container->share(function($app) {
			return new WebJobProducer(
				$app[BeanstalkKeys::HOST],
				$app[BeanstalkKeys::PORT],
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
	}

	/** @inheritdoc */
	public function boot(Pimple $container) { }
}
