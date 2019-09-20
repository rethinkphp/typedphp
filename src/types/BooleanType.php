<?php

namespace rethink\typedphp\types;

/**
 * Class BooleanType
 *
 * @package rethink\typedphp\types
 */
class BooleanType implements PrimitiveType
{
    public static function name()
    {
        return 'boolean';
    }

    public static function toArray()
    {
        return [
            'type' => 'boolean',
        ];
    }
}
