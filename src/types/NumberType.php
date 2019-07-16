<?php

namespace typedphp\types;

/**
 * Class NumberType
 *
 * @package typedphp\types
 */
class NumberType implements PrimitiveType
{
    public static function name()
    {
        return 'number';
    }

    public static function toArray()
    {
        return [
            'type' => 'number',
        ];
    }

    public static function rules()
    {
        return [

        ];
    }
}
