<?php

namespace rethink\typedphp\types;

use rethink\typedphp\TypeParser;

/**
 * ProductType with dynamic fields.
 *
 * @package rethink\typedphp\types
 */
abstract class DynamicType extends ProductType
{
    /**
     * @return array<string, array>
     */
    abstract public static function fields(): array;
}
