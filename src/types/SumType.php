<?php

namespace rethink\typedphp\types;

/**
 * Class SumType
 * 
 * @package rethink\typedphp\types
 */
abstract class SumType implements Type
{
    public static function name()
    {
        $parts = explode('\\', static::class);

        $name = end($parts);

        return substr($name, 0, strlen($name) - 4);
    }

    public static function toArray()
    {
        $class = new \ReflectionClass(static::class);
        
        return [
            'type' => 'string',
            'enum' => static::composite(),
        ];
    }
    
    abstract public static function composite();
}
