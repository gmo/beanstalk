<?php

namespace GMO\Beanstalk;

use GMO\DependencyInjection\ServiceProviderInterface;
use Pimple;

/**
 * Service provider for GMO\DependencyInjection
 *
 * @deprecated Use {@see BeanstalkSilex1ServiceProvider} or {@see BeanstalkPimple3ServiceProvider} instead.
 */
class BeanstalkServiceProvider implements ServiceProviderInterface
{
    public function register(Pimple $container)
    {
        $sp = new BeanstalkPimple1ServiceProvider();
        $sp->register($container);
    }

    public function boot(Pimple $container)
    {
    }
}
