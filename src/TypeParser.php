<?php

namespace rethink\typedphp;

use rethink\typedphp\types\BooleanType;
use rethink\typedphp\types\BinaryType;
use rethink\typedphp\types\DateType;
use rethink\typedphp\types\DictType;
use rethink\typedphp\types\InputType;
use rethink\typedphp\types\IntegerType;
use rethink\typedphp\types\ProductType;
use rethink\typedphp\types\NumberType;
use rethink\typedphp\types\StringType;
use rethink\typedphp\types\SumType;
use rethink\typedphp\types\TimestampType;
use rethink\typedphp\types\Type;
use phpDocumentor\Reflection\DocBlockFactory;

/**
 * Class TypeParser
 *
 * @package rethink\typedphp
 */
class TypeParser
{
    const MODE_JSON_SCHEMA = 1;
    const MODE_OPEN_API    = 2;
    const MODE_REF_SCHEMA  = 4;

    protected $mode = 0;
    protected $builtinTypes = [];

    protected $schemas = [];

    public function __construct($mode)
    {
        $this->mode = $mode;

        $this->registerBuiltinType(IntegerType::class);
        $this->registerBuiltinType(NumberType::class);
        $this->registerBuiltinType(StringType::class);
        $this->registerBuiltinType(BooleanType::class);
        $this->registerBuiltinType(DictType::class);

        $this->registerBuiltinType(BinaryType::class);
        $this->registerBuiltinType(TimestampType::class);
        $this->registerBuiltinType(DateType::class);
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

        $matches = [];
        if (is_string($definition) && preg_match('/\[(.*?)\]/', $definition, $matches)) {
            return [$required, $this->parseArrayField([$matches[1]])];
        }

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
            'items' => $this->parse($definition[0]),
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

        $nullable = $definition[strlen($definition) - 1] === '?';

        if ($nullable) {
            $definition = trim($definition, '?');
        }

        $definitionName = $definition::name();

        if (($this->mode & self::MODE_REF_SCHEMA) && isset($this->schemas[$definition])) {
            return $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        }

        $reflection = new \ReflectionClass($definition);
        $properties = [];
        $requiredFields = [];

        foreach ($reflection->getStaticProperties() as $property => $tmpDefinition) {
            list($required, $schema) = $this->parseField($tmpDefinition);
            $comment = $reflection->getProperty($property)->getDocComment();
            if ($comment) {
                $docblock = DocBlockFactory::createInstance()->create($comment);
                $schema['title'] = trim($docblock->getSummary());
                $schema['description'] = trim($docblock->getDescription()->render());
            }
            $properties[$property] = $schema;

            if ($required) {
                $requiredFields[] = $property;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
            'required' => $requiredFields,
        ];

        if ($this->mode & self::MODE_REF_SCHEMA) {
            $this->schemas[$definitionName] = $schema;
            return $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        } else {
            return $this->makeNullableSchema($schema, $nullable);
        }
    }

    protected function parseEnum(string $definition)
    {
        $nullable = $definition[strlen($definition) - 1] === '?';

        if ($nullable) {
            $definition = trim($definition, '?');
        }

        $definitionName = $definition::name();

        if ($this->mode & self::MODE_REF_SCHEMA) {
            $this->schemas[$definitionName] = $definition::toArray();
            return $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        } else {
            return $this->makeNullableSchema($definition::toArray(), $nullable);
        }
    }

    protected function makeNullableSchema(array $schema, $nullable)
    {
        if ($this->mode & self::MODE_OPEN_API) {
            // OpenAPI specficition does not support this, just ingore the nullable setting.
            return $schema;
        }

        if (! $nullable) {
            return $schema;
        }

        return [
            'anyOf' => [
                ['type' => 'null'],
                $schema,
            ]
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

        if (($this->mode & self::MODE_JSON_SCHEMA) && $nullable) {
            $schema['type'] = [$schema['type'], 'null'];
        } else if (($this->mode & self::MODE_OPEN_API) && $nullable) {
            $schema['nullable'] = $nullable;
        }

        return $schema;
    }

    protected function parseString($definition)
    {
        $newDefinition = trim($definition, '?');
        if (is_subclass_of($newDefinition, ProductType::class)) {
            return $this->parseObject($definition);
        } elseif (is_subclass_of($newDefinition, SumType::class)) {
            return $this->parseEnum($definition);
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
    public function parse($definition)
    {
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

    public function getSchemas()
    {
        return $this->schemas;
    }
}
