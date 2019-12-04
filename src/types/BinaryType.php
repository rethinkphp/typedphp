<?php

namespace rethink\typedphp\types;

/**
 * Class BinaryType
 *
 * @package rethink\typedphp\types
 */
class BinaryType implements PrimitiveType
{
    public static function name()
    {
        return 'binary';
    }

    public static function toArray()
    {
        return [
            'type' => 'string',
            'format' => 'binary',
        ];
    }
}
