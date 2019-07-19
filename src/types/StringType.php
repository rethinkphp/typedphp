<?php

namespace rethink\typedphp\types;

/**
 * Class StringType
 *
 * @package rethink\typedphp\types
 */
class StringType implements PrimitiveType
{
    public static function name()
    {
        return 'string';
    }

    public static function toArray()
    {
        return [
            'type' => 'string',
        ];
    }
}
