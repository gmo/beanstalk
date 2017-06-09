<?php

namespace GMO\Beanstalk\Tube;

use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Str;

class TubeCollection extends ArrayCollection
{
    /**
     * Additional functionality:
     * One or more strings can be passed in to match to tube name
     *
     * @param string|string[]|callable|null $p
     *
     * @return static
     */
    public function filter($p)
    {
        if (is_string($p)) {
            $p = array($p);
        }
        if (is_array($p)) {
            $terms = $p;
            $p = function (Tube $tube) use ($terms) {
                foreach ($terms as $term) {
                    if (Str::contains($tube->name(), $term, false)) {
                        return true;
                    }
                }

                return false;
            };
        }

        return parent::filter($p);
    }

    /**
     * @inheritdoc
     * @return Tube|null
     */
    public function get($key, $default = null)
    {
        return parent::get($key, $default);
    }

    /**
     * @inheritdoc
     * @return Tube|false
     */
    public function first()
    {
        return parent::first();
    }

    /**
     * @inheritdoc
     * @return Tube|false
     */
    public function last()
    {
        return parent::last();
    }

    /**
     * @inheritdoc
     * @return Tube
     */
    public function offsetGet($offset)
    {
        return parent::offsetGet($offset);
    }

    /**
     * @inheritdoc
     * @return Tube|null
     */
    public function remove($key)
    {
        return parent::remove($key);
    }

    /**
     * @inheritdoc
     * @return Tube|null
     */
    public function removeFirst()
    {
        return parent::removeFirst();
    }

    /**
     * @inheritdoc
     * @return Tube|null
     */
    public function removeLast()
    {
        return parent::removeLast();
    }
}
