<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Bridge\Pimple3;

use Gmo\Beanstalk\Console\Command;
use Gmo\Beanstalk\Manager\WorkerManager;
use Gmo\Beanstalk\Queue\Queue;
use Gmo\Beanstalk\Queue\WebJobProducer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console;

/**
 * Service provider for Pimple v3.
 */
class BeanstalkServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['beanstalk.host'] = 'localhost';
        $app['beanstalk.port'] = 11300;

        $app['beanstalk.worker_manager.directory'] = null;
        $app['beanstalk.queue.logger'] =
        $app['beanstalk.worker_manager.logger'] = function ($app) {
            if (isset($app['logger.new'])) {
                return $app['logger.new']('Queue');
            } elseif (isset($app['logger'])) {
                return $app['logger'];
            } else {
                return new NullLogger();
            }
        };

        $app['beanstalk.queue'] = function ($app) {
            return new Queue(
                $app['beanstalk.host'],
                $app['beanstalk.port'],
                $app['beanstalk.queue.logger']
            );
        };

        $app['beanstalk.queue.web_job_producer'] = function ($app) {
            return new WebJobProducer(
                $app['beanstalk.queue'],
                $app['beanstalk.queue.logger']
            );
        };

        $app['beanstalk.worker_manager'] = function ($app) {
            return new WorkerManager(
                $app['beanstalk.worker_manager.directory'],
                $app['beanstalk.worker_manager.logger'],
                $app['beanstalk.host'],
                $app['beanstalk.port']
            );
        };

        $app['beanstalk.console_commands.queue_prefix'] = 'queue';
        $app['beanstalk.console_commands'] = function ($app) {
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
        };

        $app['beanstalk.console_commands.auto_add'] = true;
        if (isset($app['console'])) {
            $app->extend('console', function ($console, $app) {
                if (!$app['beanstalk.console_commands.auto_add'] || !$console instanceof Console\Application) {
                    return $console;
                }
                $console->addCommands($app['beanstalk.console_commands']);

                return $console;
            });
        }
    }
}
