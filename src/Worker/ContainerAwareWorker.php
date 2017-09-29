<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Worker;

use Psr\Container\ContainerInterface;

abstract class ContainerAwareWorker extends AbstractWorker
{
    /** @var ContainerInterface */
    protected $container;

    abstract protected function createContainer(): ContainerInterface;

    public function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }
}
