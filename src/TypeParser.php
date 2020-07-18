<?php

namespace rethink\typedphp;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlockFactory;
use rethink\typedphp\types\BinaryType;
use rethink\typedphp\types\BooleanType;
use rethink\typedphp\types\DateType;
use rethink\typedphp\types\DictType;
use rethink\typedphp\types\InputType;
use rethink\typedphp\types\IntegerType;
use rethink\typedphp\types\MapType;
use rethink\typedphp\types\NumberType;
use rethink\typedphp\types\ProductType;
use rethink\typedphp\types\StringType;
use rethink\typedphp\types\SumType;
use rethink\typedphp\types\TimestampType;
use rethink\typedphp\types\TimeType;
use rethink\typedphp\types\Type;

/**
 * Class TypeParser
 *
 * @package rethink\typedphp
 */
class TypeParser
{
    const MODE_JSON_SCHEMA = 1;
    const MODE_OPEN_API = 2;
    const MODE_REF_SCHEMA = 4;

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
        $this->registerBuiltinType(TimeType::class);
    }

    public function registerBuiltinType(string $typeClass)
    {
        if (! is_subclass_of($typeClass, Type::class)) {
            throw new InvalidArgumentException("The type: $typeClass is invalid, a type should be subclass of Type");
        }

        $this->builtinTypes[$typeClass::name()] = $typeClass;
    }

    protected function getValidTypeClass($typeName)
    {
        if (! isset($this->builtinTypes[$typeName])) {
            throw new InvalidArgumentException("The type: $typeName is invalid, not such type existed");
        }

        return $this->builtinTypes[$typeName];
    }

    protected function parseField($definition)
    {
        if (is_array($definition)) {
            return [false, $this->parseArrayField($definition)];
        }

        $required = $definition[0] === '!';
        $nullable = $this->isNullable($definition);

        $matches = [];
        if (preg_match('/\[(.*?)\]/', $definition, $matches)) {
            return [$required, $this->parseArrayField([$matches[1]], $nullable)];
        }

        if ($required) {
            $definition = substr($definition, 1);
        }

        return [$required, $this->parseString($definition)];
    }

    protected function parseArrayField($definition, $nullable = false)
    {
        $schema = [
            'type' => 'array',
            'items' => $this->parseString($definition[0]),
        ];
        return $this->makeNullableSchema($schema, $nullable);
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

        $result = [
            'in' => $fetcher,
            'schema' => $schema,
        ];
        $result['required'] = $required;
        return $result;
    }

    private function isNullable(string $definition): bool
    {
        return $definition[strlen($definition) - 1] === '?';
    }

    protected function parseObject($definition)
    {
        if (is_subclass_of($definition, InputType::class)) {
            return $this->parseInputType($definition);
        }

        $nullable = $this->isNullable($definition);
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
        ];
        if ($requiredFields) {
            $schema['required'] = $requiredFields;
        }

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
        $nullable = $this->isNullable($definition);
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
        if (! $nullable) {
            return $schema;
        }

        return [
            'oneOf' => [
                ['type' => 'null'],
                $schema,
            ],
        ];
    }

    protected function parseScalar($definition)
    {
        $nullable = $this->isNullable($definition);

        if ($nullable) {
            $definition = trim($definition, '?');
        }

        $typeClass = $this->getValidTypeClass($definition);
        $schema = $typeClass::toArray();

        if ($nullable) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        return $schema;
    }

    protected function parseMap(string $definition): array
    {
        $nullable = false;
        if ($this->isNullable($definition)) {
            $nullable = true;
            $definition = trim($definition, '?');
        }

        assert(is_subclass_of($definition, MapType::class));

        $schema = $definition::toArray();

        $valueDefinition = $definition::valueType();
        if ($valueDefinition) {
            $schema['additionalProperties'] = $this->parseString($valueDefinition);
        }

        $example = $definition::example();
        if ($example) {
            $schema['example'] = $example;
        }

        $definitionName = $definition::name();
        if ($this->mode & self::MODE_REF_SCHEMA) {
            $this->schemas[$definitionName] = $schema;
            return $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        }

        return $this->makeNullableSchema($schema, $nullable);
    }

    protected function parseString($definition)
    {
        $newDefinition = trim($definition, '?');
        if (is_subclass_of($newDefinition, ProductType::class)) {
            return $this->parseObject($definition);
        } elseif (is_subclass_of($newDefinition, SumType::class)) {
            return $this->parseEnum($definition);
        } elseif (is_subclass_of($newDefinition, MapType::class)) {
            return $this->parseMap($newDefinition);
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
        } elseif (is_string($definition)) {
            return $this->parseString($definition);
        } elseif (is_object($definition) && $definition instanceof ProductType) {
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
