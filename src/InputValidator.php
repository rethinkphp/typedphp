<?php

namespace rethink\typedphp;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\Validator;
use rethink\typedphp\constraints\TypeConstraint;

/**
 * Class InputValidator
 *
 * @package rethink\typedphp
 */
class InputValidator
{
    protected $vars;

    protected $errors = [];
    protected $data = [];

    public function __construct($vars)
    {
        $this->vars = $vars;
    }

    protected function fetchData($fetcher)
    {
        return $this->vars[$fetcher] ?? [];
    }

    protected function validateInternal($definition, $data, &$result)
    {
        if (($definition['required'] ?? false) && !array_key_exists($definition['name'], $data)) {
            $this->errors[] = "The required {$definition['in']} parameter: '{$definition['name']}' is required";
            return false;
        } else if (!array_key_exists($definition['name'], $data)) {
            return false;
        }

        $schema = $definition['schema'];
        
        $factory = new Factory();
        $factory->setConstraintClass('type', TypeConstraint::class);

        $validator = new Validator($factory);

        $result = $data[$definition['name']];

        $validator->validate($result, $schema, Constraint::CHECK_MODE_COERCE_TYPES | Constraint::CHECK_MODE_TYPE_CAST);

        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->errors[] = "The type of {$definition['in']} parameter \"{$definition['name']}\" is invalid, " . lcfirst($error['message']);
            }
            return false;
        }

        return true;
    }

    public function validate(array $definitions)
    {
        $this->errors = $this->data = [];

        foreach ($definitions as $definition) {
            $data = $this->fetchData($definition['in']);

            if ($this->validateInternal($definition, $data, $result)) {
                $this->data[$definition['in']][$definition['name']] = $result;
            }
        }

        return count($this->errors) === 0;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
