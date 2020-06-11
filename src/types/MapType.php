<?php

namespace rethink\typedphp\types;

/**
 * Class MapType
 *
 * Powerful version of DictType
 *
 * @package rethink\typedphp\types
 */
abstract class MapType implements Type
{
    public static function name()
    {
        $parts = explode('\\', static::class);

        $name = end($parts);

        return substr($name, 0, strlen($name) - 4);
    }

    public static function toArray()
    {
        return [
            'type' => 'object',
        ];
    }

    abstract public static function valueType(): string;

    abstract public static function example(): array;
}
