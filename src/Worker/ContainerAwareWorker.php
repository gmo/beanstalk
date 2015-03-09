<?php
namespace GMO\Beanstalk\Worker;

abstract class ContainerAwareWorker extends AbstractWorker
{
    private $container;

    /**
     * @return \Pimple
     */
    abstract protected function getDefaultContainer();

    public function getService($name) {
        return $this->getContainer()->offsetGet($name);
    }

    /** @return \Pimple */
    public function getContainer() {
        if ($this->container === null) {
            $this->container = $this->getDefaultContainer();
        }
        return $this->container;
    }
}
