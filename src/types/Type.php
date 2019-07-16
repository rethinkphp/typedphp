<?php

namespace typedphp\types;

/**
 * Interface Type
 *
 * @package typedphp\types
 */
interface Type
{
    public static function name();
    public static function toArray();
}
