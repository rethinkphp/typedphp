<?php

namespace typedphp\types;

/**
 * Class StringType
 *
 * @package typedphp\types
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
