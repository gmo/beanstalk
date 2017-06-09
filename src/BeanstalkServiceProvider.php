<?php

namespace GMO\Beanstalk;

use Gmo\Common\Deprecated;
use GMO\DependencyInjection\ServiceProviderInterface;
use Pimple;

Deprecated::cls('\GMO\Beanstalk\BeanstalkServiceProvider', 2.0);

/**
 * Service provider for GMO\DependencyInjection
 *
 * @deprecated Use {@see BeanstalkServiceProvider} or {@see BeanstalkServiceProvider} instead.
 */
class BeanstalkServiceProvider implements ServiceProviderInterface
{
    public function register(Pimple $container)
    {
        $sp = new Bridge\Pimple1\BeanstalkServiceProvider();
        $sp->register($container);
    }

    public function boot(Pimple $container)
    {
    }
}
