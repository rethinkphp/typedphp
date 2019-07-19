<?php

namespace rethink\typedphp\types;

/**
 * Interface Type
 *
 * @package rethink\typedphp\types
 */
interface Type
{
    public static function name();
    public static function toArray();
}
