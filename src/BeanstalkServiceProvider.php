<?php
namespace GMO\Beanstalk;

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

		$container['beanstalk.host'] = '127.0.0.1';
		$container['beanstalk.port'] = 11300;

		$container['worker_manager.directory'] = null;
		$container['queue.logger'] =
		$container['worker_manager.logger'] = function() {
			return new NullLogger();
		};

		$container['queue'] = $container->share(function($app) {
			return new Queue($app['beanstalk.host'], $app['beanstalk.port'], $app['queue.logger']);
		});

		$container['queue.rpc'] = $container->share(function($app) {
			return new RpcQueue($app['beanstalk.host'], $app['beanstalk.port'], $app['queue.logger']);
		});

		$container['worker_manager'] = $container->share(function($app) {
			return new WorkerManager(
				$app['worker_manager.directory'],
				$app['worker_manager.logger'],
				$app['beanstalk.host'],
				$app['beanstalk.port']
			);
		});
	}

	/** @inheritdoc */
	public function boot(Pimple $container) { }
}
