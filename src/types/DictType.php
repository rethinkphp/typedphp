<?php

namespace rethink\typedphp\types;

/**
 * Class DictType
 * 
 * @package rethink\typedphp\types
 */
class DictType implements PrimitiveType
{
    public static function name()
    {
        return 'dict';
    }

    public static function toArray()
    {
        return [
            'type' => 'object',
        ];
    }
}
