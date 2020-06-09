<?php

namespace rethink\typedphp;

/**
 * Interface ApiInterface
 *
 * @package rethink\typedphp
 */
interface ApiInterface
{
    /**
     * @return mixed
     */
    public static function parameters();

    /**
     * @return mixed
     */
    public static function requestBody();

    public static function responses(): array;

    public static function permissions(): array;
}
