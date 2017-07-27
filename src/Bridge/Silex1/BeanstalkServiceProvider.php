<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Bridge\Silex1;

use Gmo\Beanstalk\Bridge;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Service provider for Silex v1
 */
class BeanstalkServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $sp = new Bridge\Pimple1\BeanstalkServiceProvider();
        $sp->register($app);
    }

    public function boot(Application $app)
    {
    }
}
