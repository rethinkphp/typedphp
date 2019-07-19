<?php

namespace typedphp;

/**
 * Interface ApiInterface
 *
 * @package typedphp
 */
interface ApiInterface
{
    public static function parameters();

    public static function requestBody();

    public static function responses();

    public static function permissions();
}
