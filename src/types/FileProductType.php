<?php
namespace rethink\typedphp\types;

use rethink\typedphp\TypeParser;

/**
 * Class FileProductType
 *
 * @package rethink\typedphp\types
 */
abstract class FileProductType extends ProductType
{
    public static function contentType()
    {
        return 'multipart/form-data';
    }
}
