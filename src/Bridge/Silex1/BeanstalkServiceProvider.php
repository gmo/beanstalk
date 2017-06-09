<?php

namespace Gmo\Beanstalk\Bridge\Silex1;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Service provider for Silex v1
 */
class BeanstalkServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $sp = new BeanstalkServiceProvider();
        $sp->register($app);
    }

    public function boot(Application $app)
    {
    }
}
