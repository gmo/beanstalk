<?php

namespace Gmo\Beanstalk\Bridge\Pimple1;

use GMO\Beanstalk\Console\Command;
use GMO\Beanstalk\Manager\WorkerManager;
use GMO\Beanstalk\Queue\Queue;
use GMO\Beanstalk\Queue\WebJobProducer;
use Pimple;
use Psr\Log\NullLogger;
use Symfony\Component\Console;

/**
 * Service Provider for Pimple v1 syntax.
 */
class BeanstalkServiceProvider
{
    public function register(Pimple $app)
    {
        $app['beanstalk.host'] = 'localhost';
        $app['beanstalk.port'] = 11300;

        $app['beanstalk.worker_manager.directory'] = null;
        $app['beanstalk.queue.logger'] =
        $app['beanstalk.worker_manager.logger'] = $app->share(
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

        $app['beanstalk.queue'] = $app->share(
            function ($app) {
                return new Queue(
                    $app['beanstalk.host'],
                    $app['beanstalk.port'],
                    $app['beanstalk.queue.logger']
                );
            }
        );

        $app['beanstalk.queue.web_job_producer'] = $app->share(
            function ($app) {
                return new WebJobProducer(
                    $app['beanstalk.queue'],
                    $app['beanstalk.queue.logger']
                );
            }
        );

        $app['beanstalk.worker_manager'] = $app->share(
            function ($app) {
                return new WorkerManager(
                    $app['beanstalk.worker_manager.directory'],
                    $app['beanstalk.worker_manager.logger'],
                    $app['beanstalk.host'],
                    $app['beanstalk.port']
                );
            }
        );

        $app['beanstalk.console_commands.queue_prefix'] = 'queue';
        $app['beanstalk.console_commands'] = $app->share(
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

        $app['beanstalk.console_commands.auto_add'] = true;
        if (isset($app['console'])) {
            $app['console'] = $app->share(
                $app->extend(
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
