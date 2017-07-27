<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Worker;

/**
 * @property \Pimple $container
 */
abstract class ContainerAwareWorker extends AbstractWorker
{
    private $container;

    /**
     * @return \Pimple
     */
    abstract protected function getDefaultContainer();

    public function getService($name)
    {
        return $this->getContainer()->offsetGet($name);
    }

    /** @return \Pimple */
    public function getContainer()
    {
        if ($this->container === null) {
            $this->container = $this->getDefaultContainer();
        }

        return $this->container;
    }

    public function __get($name)
    {
        if ($name === 'container') {
            return $this->getContainer();
        }
        throw new \BadMethodCallException();
    }
}
