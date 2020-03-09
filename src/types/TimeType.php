<?php

namespace rethink\typedphp\types;

/**
 * Class TimeType
 *
 * @package rethink\typedphp\types
 */
class TimeType implements PrimitiveType
{
    public static function name()
    {
        return 'time';
    }

    public static function toArray()
    {
        return [
            'type' => 'string',
            'pattern' => '^\d{2}-\d{2}-\d{2}$',
        ];
    }
}
