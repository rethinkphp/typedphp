<?php

namespace rethink\typedphp;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlockFactory;
use rethink\typedphp\types\BinaryType;
use rethink\typedphp\types\BooleanType;
use rethink\typedphp\types\DateType;
use rethink\typedphp\types\DictType;
use rethink\typedphp\types\DynamicType;
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
use rethink\typedphp\types\UnionType;

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
    const MODE_OPEN_API_31 = 8;

    protected $mode = 0;
    protected $builtinTypes = [];

    protected $schemas = [];

    protected $object_chains = [];

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
        if (is_string($definition)) {
            $nullable = $this->isNullable($definition);
            if ($nullable) {
                $definition = trim($definition, '?');
            }
            $definition = [trim($definition, '[]')];
        } else {
            $nullable = false;
        }

        $schema = [
            'type' => 'array',
            'items' => $this->parse($definition[0]),
        ];

        return $this->makeNullableSchema($schema, $nullable);
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

        $isNestedType = in_array($definitionName, $this->object_chains);

        if (($this->mode & self::MODE_REF_SCHEMA) && isset($this->schemas[$definition])) {
            return $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        }

        $this->object_chains[] = $definitionName;

        if ($isNestedType) {
            $result = $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        } else {
            $result = $this->parseObjectSchema($definitionName, $definition, $nullable);
        }

        array_pop($this->object_chains);

        return $result;
    }

    protected function parseObjectSchema(string $name, string $definition, bool $nullable): array
    {
        if (!isset($this->schemas[$name])) {
            $reflection = new \ReflectionClass($definition);
            $properties = [];
            $requiredFields = [];

            foreach ($reflection->getStaticProperties() as $property => $tmpDefinition) {
                list($required, $schema) = $this->parseField($tmpDefinition);
                $comment = $reflection->getProperty($property)->getDocComment();
                if ($comment) {
                    $docblock = DocBlockFactory::createInstance()->create($comment);
                    $title = trim($docblock->getSummary());
                    if ($title) {
                        $schema['title'] = $title;
                    }
                    $description = trim($docblock->getDescription()->render());
                    if ($description) {
                        $schema['description'] = $description;
                    }

                    $tag = $docblock->getTagsByName('enum')[0] ?? null;
                    if ($tag) {
                        $schema['type'] = 'string';
                        $schema['enum'] = preg_split("/\s*[,]\s*/u", $tag->getDescription()->render(), -1, PREG_SPLIT_NO_EMPTY);
                        unset($schema['$ref']);
                    }
                }
                $properties[$property] = $schema;

                if ($required) {
                    $requiredFields[] = $property;
                }
            }

            if ($reflection->isSubclassOf(DynamicType::class)) {
                $properties = array_merge($properties, $definition::fields());

                $sharedSchemas = $definition::referencedSchemas();
                foreach ($sharedSchemas as $schemaName => $sharedSchema) {
                    $this->registerSchema($schemaName, $sharedSchema);
                }
            }

            $schema = [
                'type' => 'object',
                'properties' => (object)$properties,
            ];
            if ($requiredFields) {
                $schema['required'] = $requiredFields;
            }

            $this->schemas[$name] = $schema;
        } else {
            $schema = $this->schemas[$name];
        }

        if (($this->mode & self::MODE_REF_SCHEMA)) {
            $result = $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $name,
            ], $nullable);
        } else {
            $result = $this->makeNullableSchema($schema, $nullable);
        }

        return $result;
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

    protected function isVersion31()
    {
        return $this->mode & self::MODE_OPEN_API_31;
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

    protected function parseUnion(string $definition): array
    {
        $nullable = false;
        if ($this->isNullable($definition)) {
            $nullable = true;
            $definition = trim($definition, '?');
        }

        assert(is_subclass_of($definition, UnionType::class));

        $types = [];
        foreach ($definition::allowedTypes() as $allowedType) {
            $types[] = $this->parse($allowedType);
        }

        if ($this->mode & self::MODE_REF_SCHEMA) {
            $definitionName = $definition::name();
            $this->schemas[$definitionName] = [
                'oneOf' => $types,
            ];

            return $this->makeNullableSchema([
                '$ref' => '#/components/schemas/' . $definitionName,
            ], $nullable);
        }

        if ($nullable) {
            $types[] = ['type' => 'null'];
        }

        return [
            'oneOf' => $types,
        ];
    }

    protected function isLiteralObject(string $definition): bool
    {
        $core = trim($definition, '?');
        return strlen($core) > 1 && $core[0] === '{' && $core[strlen($core) - 1] === '}';
    }

    /**
     * Split the inner content of a literal object into individual field strings,
     * respecting nesting of {} and [].
     */
    protected function splitLiteralObjectFields(string $inner): array
    {
        $fields = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($inner); $i++) {
            $c = $inner[$i];
            if ($c === '{' || $c === '[') {
                $depth++;
                $current .= $c;
            } elseif ($c === '}' || $c === ']') {
                $depth--;
                $current .= $c;
            } elseif ($c === ',' && $depth === 0) {
                $fields[] = $current;
                $current = '';
            } else {
                $current .= $c;
            }
        }

        if (trim($current) !== '') {
            $fields[] = $current;
        }

        return $fields;
    }

    /**
     * Parse a literal inline object definition, e.g.:
     *   {name: string, age: integer?, is_admin?: boolean}
     *
     * - "field?: type"  → field is optional (not in required)
     * - "field: type?"  → field is nullable
     */
    protected function parseLiteralObject(string $definition): array
    {
        $nullable = $this->isNullable($definition);
        if ($nullable) {
            $definition = substr($definition, 0, -1);
        }

        // Strip outer { }
        $inner = substr($definition, 1, -1);

        $rawFields = $this->splitLiteralObjectFields($inner);

        $properties = [];
        $requiredFields = [];

        foreach ($rawFields as $rawField) {
            $rawField = trim($rawField);
            $colonPos = strpos($rawField, ':');
            $fieldName = trim(substr($rawField, 0, $colonPos));
            $typeExpr  = trim(substr($rawField, $colonPos + 1));

            $optional = false;
            if (substr($fieldName, -1) === '?') {
                $optional = true;
                $fieldName = substr($fieldName, 0, -1);
            }

            $properties[$fieldName] = $this->parse($typeExpr);

            if (!$optional) {
                $requiredFields[] = $fieldName;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => (object)$properties,
        ];
        if ($requiredFields) {
            $schema['required'] = $requiredFields;
        }

        return $this->makeNullableSchema($schema, $nullable);
    }

    protected function parseString($definition)
    {
        static $cached = [];
        $newDefinition = trim($definition, '?');

        $key = $definition;
        if (is_subclass_of($newDefinition, MapType::class)) {
            $key = $newDefinition;
        }

        $key = $this->mode & self::MODE_REF_SCHEMA ? 'ref:' . $key : $key;

        if (isset($cached[$key])) {
            return $cached[$key];
        }

        if (is_subclass_of($newDefinition, DynamicType::class)) {
            return $this->parseObject($definition); // no cache for DynamicType
        }

        if (is_subclass_of($newDefinition, ProductType::class)) {
            $cached[$key] = $this->parseObject($definition);
        } elseif (is_subclass_of($newDefinition, SumType::class)) {
            $cached[$key] = $this->parseEnum($definition);
        } elseif (is_subclass_of($newDefinition, MapType::class)) {
            $cached[$key] = $this->parseMap($definition);
        } elseif (is_subclass_of($newDefinition, UnionType::class)) {
            $cached[$key] = $this->parseUnion($definition);
        } elseif ($newDefinition[0] === '[' && $newDefinition[strlen($newDefinition) - 1] === ']') {
            $cached[$key] = $this->parseArray($definition);
        } elseif ($this->isLiteralObject($definition)) {
            $cached[$key] = $this->parseLiteralObject($definition);
        } else {
            $cached[$key] = $this->parseScalar($definition);
        }
        return $cached[$key];
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

    public function registerSchema(string $name, array $schema): array
    {
        if (isset($this->schemas[$name])) {
            throw new InvalidArgumentException("The schema: $name is already registered");
        }

        $this->schemas[$name] = $schema;

        return [
            '$ref' => '#/components/schemas/' . $name,
        ];
    }
}
