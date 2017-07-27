<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Queue\Response;

use Bolt\Collection\Bag;
use Gmo\Common\Str;

abstract class AbstractStats extends Bag
{
    public static function from($collection)
    {
        $data = [];
        foreach ($collection as $key => $value) {
            $data[$key] = static::convertToType($value);
        }

        return new static($data);
    }

    protected static function convertToType($value)
    {
        if (is_numeric($value)) {
            if (Str::contains($value, '.')) {
                return floatval($value);
            }

            return intval($value);
        }

        return $value;
    }
}
