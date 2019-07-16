<?php

namespace typedphp;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

/**
 * Class TypeValidator
 *
 * @package typedphp
 */
class TypeValidator
{
    protected $errors = [];

    public function validate($data, $definition)
    {
        $this->errors = [];

        $validator = new Validator();

        $validator->validate($data, $definition, Constraint::CHECK_MODE_TYPE_CAST);

        if ($validator->isValid()) {
            return true;
        }

        foreach ($validator->getErrors() as $error) {
            $this->errors[] = $this->formatError($error);
        }

        return false;
    }

    protected function formatError(array $error)
    {
        if (empty($error['property'])) {
            return $error['message'];
        } else {
            return "The data of \"${error['property']}\" is invalid, " . lcfirst($error['message']);
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
