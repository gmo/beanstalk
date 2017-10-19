<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Bridge\Pimple1;

use Gmo\Beanstalk\Console\QueueConsoleApplication;
use Gmo\Beanstalk\Log\JobProcessor;
use Gmo\Beanstalk\Log\WorkerProcessor;
use Gmo\Beanstalk\Manager\WorkerManager;
use Gmo\Beanstalk\Queue\Queue;
use Gmo\Beanstalk\Queue\WebJobProducer;
use Psr\Log\NullLogger;
use Symfony\Component\Console;

/**
 * Service Provider for Pimple v1 syntax.
 */
class BeanstalkServiceProvider
{
    public function register(\Pimple $app)
    {
        $app['beanstalk.host'] = 'localhost';
        $app['beanstalk.port'] = 11300;

        $app['beanstalk.worker_manager.directory'] = null;
        $app['beanstalk.queue.logger'] =
        $app['beanstalk.worker_manager.logger'] = $app->share(
            function ($app) {
                if (isset($app['logger.new'])) {
                    return $app['logger.new']('Queue');
                }
                if (isset($app['logger'])) {
                    return $app['logger'];
                }

                return new NullLogger();
            }
        );

        $app['beanstalk.queue'] = $app->share(
            function ($app) {
                $queue = new Queue(
                    $app['beanstalk.host'],
                    $app['beanstalk.port'],
                    $app['beanstalk.queue.logger']
                );
                $queue->setJobProcessor($app['beanstalk.logger.processor.job']);

                return $queue;
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

        $app['beanstalk.console_commands.auto_add'] = true;
        if (isset($app['console'])) {
            $app['console'] = $app->share(
                $app->extend(
                    'console',
                    function ($console, $app) {
                        if (!$app['beanstalk.console_commands.auto_add'] || !$console instanceof Console\Application) {
                            return $console;
                        }
                        $console->addCommands(QueueConsoleApplication::getCommands());

                        return $console;
                    }
                )
            );
        }

        $app['beanstalk.logger.processor.job'] = $app->share(
            function () {
                return new JobProcessor();
            }
        );

        $app['beanstalk.logger.processor.worker'] = $app->share(
            function () {
                return new WorkerProcessor();
            }
        );

        if (isset($app['logger.processors'])) {
            $app['logger.processors'] = $app->share(
                $app->extend(
                    'logger.processors',
                    function (iterable $processors, $app) {
                        $processors[] = $app['beanstalk.logger.processor.job'];
                        $processors[] = $app['beanstalk.logger.processor.worker'];

                        return $processors;
                    }
                )
            );
        }
    }
}
