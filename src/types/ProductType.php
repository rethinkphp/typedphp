<?php

namespace rethink\typedphp\types;

use rethink\typedphp\TypeParser;

/**
 * Class ProductType
 *
 * @package rethink\typedphp\types
 */
abstract class ProductType implements Type
{
    public static function contentType()
    {
        return 'application/json';
    }

    public static function name()
    {
        $parts = explode('\\', static::class);

        $name = end($parts);

        return substr($name, 0, strlen($name) - 4);
    }

    public static function toArray()
    {
        $parser = new TypeParser(static::class);

        return $parser->parse();
    }
}
