<?php
namespace GMO\Beanstalk\Helper;

use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Exception\NotSerializableException;
use GMO\Common\ISerializable;
use Traversable;

class JobDataSerializer {

	public function serialize($data) {
		if ($data instanceof ISerializable) {
			return $data->toJson();
		}
		if ($data instanceof Traversable) {
			$data = iterator_to_array($data, true);
		}
		if (is_scalar($data)) {
			$data = array( 'data' => $data );
		}
		if (is_array($data)) {
			foreach ($data as $key => &$value) {
				if ($value instanceof ISerializable) {
					$value = $value->toArray();
				}
			}
			$data = json_encode($data);
		}
		return $data;
	}

	public function unserialize($data) {
		$params = new ArrayCollection(json_decode($data, true));
		if ($params->count() === 1 && $params->containsKey('data')) {
			return $params['data'];
		}

		if ($params->containsKey('class')) {
			/** @var ISerializable|string $cls */
			$cls = $params['class'];
			if (!class_exists($cls)) {
				throw new NotSerializableException($cls . ' does not exist');
			}
			return $cls::fromArray($params->toArray());
		}

		foreach ($params as $key => $value) {
			if (is_string($value)) {
				$params[$key] = trim($value);
			} elseif (is_array($value) && array_key_exists('class', $value)) {
				/** @var ISerializable|string $cls */
				$cls = $value['class'];
				if (!class_exists($cls)) {
					throw new NotSerializableException($cls . ' does not exist');
				}
				$params[$key] = $cls::fromArray($value);
			}
		}
		return $params;
	}
}
