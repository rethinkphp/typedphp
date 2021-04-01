<?php

declare(strict_types=1);

namespace rethink\typedphp\constraints;

use JsonSchema\Constraints\Factory;

class TypeConstraint extends \JsonSchema\Constraints\TypeConstraint
{
    protected function toBoolean($value)
    {
        if ($value === 1 || $value === '1') {
            return true;
        } elseif ($value === 0 || $value === '0') {
            return false;
        } else {
            return parent::toBoolean($value);
        }
    }
}
