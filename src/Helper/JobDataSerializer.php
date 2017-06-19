<?php

namespace GMO\Beanstalk\Helper;

use Bolt\Collection\Bag;
use GMO\Common\Exception\NotSerializableException;
use GMO\Common\Json;
use Gmo\Common\Serialization\SerializableInterface;
use Traversable;

class JobDataSerializer
{
    public function serialize($data)
    {
        if ($data instanceof SerializableInterface) {
            return Json::dump($data->toArray());
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data, true);
        }
        if (is_scalar($data)) {
            $data = array('data' => $data);
        }
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                if ($value instanceof SerializableInterface) {
                    $value = $value->toArray();
                }
            }
            $data = Json::dump($data);
        }

        return $data;
    }

    public function unserialize($data)
    {
        $params = Bag::from(Json::parse($data));
        if ($params->count() === 1 && $params->has('data')) {
            $data = $params['data'];

            if ($this->nativeUnserialize($data, $unserialized)) {
                return $unserialized;
            }
        }

        if ($params->has('class')) {
            /** @var SerializableInterface|string $cls */
            $cls = $params['class'];
            if (!class_exists($cls)) {
                throw new NotSerializableException($cls . ' does not exist');
            }

            return $cls::fromArray($params->toArray());
        }

        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);

                if ($this->nativeUnserialize($value, $data)) {
                    $value = $data;
                }

                $params[$key] = $value;
            } elseif (is_array($value) && array_key_exists('class', $value)) {
                /** @var SerializableInterface|string $cls */
                $cls = $value['class'];
                if (!class_exists($cls)) {
                    throw new NotSerializableException($cls . ' does not exist');
                }
                $params[$key] = $cls::fromArray($value);
            }
        }

        return $params;
    }

    /**
     * @param string $value serialized data
     * @param mixed  $data  Pass variable to set unserialized data to (if successful)
     *
     * @return bool Whether the unserialization was successful.
     */
    private function nativeUnserialize($value, &$data)
    {
        if ($value === 'b:0;') { // serialized representation of false
            $data = false;

            return true;
        }

        $data = @unserialize($value);

        return $data !== false;
    }
}
