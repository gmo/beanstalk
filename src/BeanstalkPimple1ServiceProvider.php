<?php
namespace GMO\Beanstalk;

use GMO\Beanstalk\Console\Command;
use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Beanstalk\Queue\Queue;
use GMO\Beanstalk\Queue\WebJobProducer;
use Pimple;
use Psr\Log\NullLogger;
use Symfony\Component\Console;

/**
 * Service Provider for Pimple v1 syntax.
 * 
 * @internal Use {@see BeanstalkSilex1ServiceProvider} instead.
 */
class BeanstalkPimple1ServiceProvider
{
    public function register(Pimple $container)
    {
        $container['beanstalk.host'] = 'localhost';
        $container['beanstalk.port'] = 11300;

        $container['beanstalk.worker_manager.directory'] = null;
        $container['beanstalk.queue.logger'] =
        $container['beanstalk.worker_manager.logger'] = $container->share(
            function ($app) {
                if (isset($app['logger.new'])) {
                    return $app['logger.new']('Queue');
                } elseif (isset($app['logger'])) {
                    return $app['logger'];
                } else {
                    return new NullLogger();
                }
            }
        );

        $container['beanstalk.queue'] = $container->share(
            function ($app) {
                return new Queue(
                    $app['beanstalk.host'],
                    $app['beanstalk.port'],
                    $app['beanstalk.queue.logger']
                );
            }
        );

        $container['beanstalk.queue.web_job_producer'] = $container->share(
            function ($app) {
                return new WebJobProducer(
                    $app['beanstalk.queue'],
                    $app['beanstalk.queue.logger']
                );
            }
        );

        $container['beanstalk.worker_manager'] = $container->share(
            function ($app) {
                return new WorkerManager(
                    $app['beanstalk.worker_manager.directory'],
                    $app['beanstalk.worker_manager.logger'],
                    $app['beanstalk.host'],
                    $app['beanstalk.port']
                );
            }
        );

        $container['beanstalk.console_commands.queue_prefix'] = 'queue';
        $container['beanstalk.console_commands'] = $container->share(
            function ($app) {
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
            }
        );

        $container['beanstalk.console_commands.auto_add'] = true;
        if (isset($container['console'])) {
            $container['console'] = $container->share(
                $container->extend(
                    'console',
                    function ($console, $app) {
                        if (!$app['beanstalk.console_commands.auto_add'] || !$console instanceof Console\Application) {
                            return $console;
                        }
                        $console->addCommands($app['beanstalk.console_commands']);

                        return $console;
                    }
                )
            );
        }
    }
}
