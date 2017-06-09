<?php

namespace GMO\Beanstalk;

use Gmo\Common\Deprecated;

Deprecated::cls('\GMO\Beanstalk\BeanstalkSilex1ServiceProvider', 2.7, '\Gmo\Beanstalk\Bridge\Silex1\BeanstalkServiceProvider');

/**
 * @deprecated will be removed in 3.0.
 */
class BeanstalkSilex1ServiceProvider extends Bridge\Silex1\BeanstalkServiceProvider
{
}
