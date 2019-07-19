<?php

namespace rethink\typedphp\types;

/**
 * Class IntegerType
 *
 * @package rethink\typedphp\types
 */
class IntegerType implements PrimitiveType
{
    public static function name()
    {
        return 'integer';
    }

    public static function toArray()
    {
        return [
            'type' => 'integer',
        ];
    }
}
