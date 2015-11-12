<?php
namespace GMO\Beanstalk\Queue\Response;

use GMO\Common\Collections\ArrayCollection;
use GMO\Common\Str;

abstract class AbstractStats extends ArrayCollection {

	protected static function convertToType($value) {
		if (is_numeric($value)) {
			if (Str::contains($value, '.')) {
				return floatval($value);
			}
			return intval($value);
		}
		return $value;
	}

	public function __construct($elements = array()) {
		parent::__construct($elements);
		foreach ($this as $key => $value) {
			$this[$key] = static::convertToType($value);
		}
	}
}
