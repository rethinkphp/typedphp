<?php

namespace rethink\typedphp\types;

use rethink\typedphp\TypeParser;

abstract class UnionType implements Type
{
    public static function name()
    {
        $parts = explode('\\', static::class);

        $name = end($parts);

        return substr($name, 0, strlen($name) - 4);
    }

    abstract public static function allowedTypes(): array;

    public static function toArray()
    {
        throw new \Exception('Should not be called');
    }
}
