<?php

namespace typedphp;

use typedphp\types\InputType;
use typedphp\types\IntegerType;
use typedphp\types\ProductType;
use typedphp\types\NumberType;
use typedphp\types\StringType;
use typedphp\types\TimestampType;
use typedphp\types\Type;
use phpDocumentor\Reflection\DocBlockFactory;

/**
 * Class TypeParser
 *
 * @package typedphp
 */
class TypeParser
{
    const MODE_JSON_SCHEMA = 1;
    const MODE_OPEN_API = 2;

    protected $mode;
    protected $builtinTypes = [];

    public function __construct()
    {
        $this->registerBuiltinType(IntegerType::class);
        $this->registerBuiltinType(NumberType::class);
        $this->registerBuiltinType(StringType::class);

        $this->registerBuiltinType(TimestampType::class);
    }

    public function registerBuiltinType(string $typeClass)
    {
        if (!is_subclass_of($typeClass, Type::class)) {
            throw new \InvalidArgumentException("The type: $typeClass is invalid, a type should be subclass of Type");
        }

        $this->builtinTypes[$typeClass::name()] = $typeClass;
    }

    protected function getValidTypeClass($typeName)
    {
        if (!isset($this->builtinTypes[$typeName])) {
            throw new \InvalidArgumentException("The type: $typeName is invalid, not such type existed");
        }

        return $this->builtinTypes[$typeName];
    }

    protected function parseField($definition)
    {
        if (is_array($definition)) {
            return [false, $this->parseArrayField($definition)];
        }

        $required = $definition[0] === '!';

        if ($required) {
            $definition = substr($definition, 1);
        }

        return [$required, $this->parseString($definition)];
    }

    protected function parseArrayField($definition)
    {
        return [
            'type' => 'array',
            'items' => $this->parseString($definition[0]),
        ];
    }

    protected function parseArray($definition)
    {
        return [
            'type' => 'array',
            'items' => $this->parse($definition[0], $this->mode),
        ];
    }

    protected function parseInputType($definition)
    {
        $reflection = new \ReflectionClass($definition);
        $results = [];

        foreach ($reflection->getStaticProperties() as $property => $definition) {
            $comment = $reflection->getProperty($property)->getDocComment();
            $tmp = ['name' => $property] + $this->parseInputField($definition);

            if ($comment) {
                $docblock = DocBlockFactory::createInstance()->create($comment);
                $tmp['description'] = $docblock->getSummary() . "\n\n" . $docblock->getDescription()->render();
            }

            $results[] = $tmp;
        }

        return $results;
    }

    protected function parseInputField($definition)
    {
        $parts = explode(':', $definition, 2);

        if (count($parts) === 1) {
            $parts = ['query', $parts[0]];
        }

        list($fetcher, $definition) = $parts;

        $required = $definition[0] === '!';
        if ($required) {
            $definition = substr($definition, 1);
        }

        list($_, $schema) = $this->parseField($definition);

        return [
            'in' => $fetcher,
            'required' => $required,
            'schema' => $schema,
        ];
    }

    protected function parseObject($definition)
    {
        if (is_subclass_of($definition, InputType::class)) {
            return $this->parseInputType($definition);
        }

        $reflection = new \ReflectionClass($definition);
        $properties = [];
        $requiredFields = [];

        foreach ($reflection->getStaticProperties() as $property => $definition) {
            list($required, $schema) = $this->parseField($definition);
            $properties[$property] = $schema;

            if ($required) {
                $requiredFields[] = $property;
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $requiredFields,
        ];
    }

    protected function parseScalar($definition)
    {
        $nullable = $definition[strlen($definition) - 1] === '?';

        if ($nullable) {
            $definition = trim($definition, '?');
        }

        $typeClass = $this->getValidTypeClass($definition);

        $schema = $typeClass::toArray();

        if ($this->mode === self::MODE_JSON_SCHEMA && $nullable) {
            $schema['type'] = [$schema['type'], 'null'];
        } else if ($this->mode === self::MODE_OPEN_API && $nullable) {
            $schema['nullable'] = $nullable;
        }

        return $schema;
    }

    protected function parseString($definition)
    {
        $newDefinition = trim($definition, '?');
        if (is_subclass_of($newDefinition, ProductType::class)) {
            return $this->parseObject($definition);
        } else {
            return $this->parseScalar($definition);
        }
    }

    /**
     * Parse the given definition to json schema or open api specification.
     *
     * @param mixed $definition
     * @param integer $mode
     * @return array
     */
    public function parse($definition, $mode = self::MODE_JSON_SCHEMA)
    {
        $this->mode = $mode;

        if (is_array($definition)) {
            return $this->parseArray($definition);
        } else if (is_string($definition)) {
            return $this->parseString($definition);
        } else if (is_object($definition) && $definition instanceof ProductType) {
            return $this->parseObject(get_class($definition));
        } else {
            throw new \InvalidArgumentException('The definition is invalid');
        }
    }
}
