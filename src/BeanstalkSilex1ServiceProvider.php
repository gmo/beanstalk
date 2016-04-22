<?php

namespace GMO\Beanstalk;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Service provider for Silex v1
 */
class BeanstalkSilex1ServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $sp = new BeanstalkPimple1ServiceProvider();
        $sp->register($app);
    }

    public function boot(Application $app)
    {
    }
}
