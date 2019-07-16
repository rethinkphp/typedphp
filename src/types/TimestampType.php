<?php

namespace typedphp\types;

/**
 * Class TimestampType
 *
 * @package typedphp\types
 */
class TimestampType implements PrimitiveType
{
    public static function name()
    {
        return 'timestamp';
    }

    public static function toArray()
    {
        return [
            'type' => 'string',
            'format' => 'timestamp',
            'pattern' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
        ];
    }
}
