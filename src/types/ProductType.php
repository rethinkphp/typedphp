<?php

namespace typedphp\types;

use typedphp\TypeParser;

/**
 * Class ProductType
 *
 * @package typedphp\types
 */
abstract class ProductType implements Type
{
    public static function name()
    {
        return static::class;
    }

    public static function toArray()
    {
        $parser = new TypeParser(static::class);

        return $parser->parse();
    }
}
