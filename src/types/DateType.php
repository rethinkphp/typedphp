<?php

namespace rethink\typedphp\types;

/**
 * Class DateType
 *
 * @package rethink\typedphp\types
 */
class DateType implements PrimitiveType
{
    public static function name()
    {
        return 'date';
    }

    public static function toArray()
    {
        return [
            'type' => 'string',
            'format' => 'date',
            'pattern' => '^\d{4}-\d{2}-\d{2}$',
        ];
    }
}
