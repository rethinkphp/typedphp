<?php

namespace typedphp\tests;

use PHPUnit\Framework\TestCase;
use rethink\typedphp\InputValidator;
use rethink\typedphp\TypeParser;
use rethink\typedphp\types\DictType;
use rethink\typedphp\types\InputType;
use rethink\typedphp\types\MapType;
use rethink\typedphp\types\ProductType;
use rethink\typedphp\types\SumType;
use rethink\typedphp\types\UnionType;
use rethink\typedphp\TypeValidator;

/**
 * Class TypeTest
 *
 * @package typedphp
 */
class TypeTest extends TestCase
{

    protected function product001Schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'is_admin' => ['type' => 'boolean'],
                'file' => ['type' => 'string', 'format' => 'binary',],
                'nullable_field' => ['type' => ['string', 'null']],
                'date' => [
                    'type' => ['string', 'null'],
                    'format' => 'date',
                    'pattern' => '^\d{4}-\d{2}-\d{2}$',
                ],
                'time' => [
                    'type' => 'string',
                    'pattern' => '^\d{2}:\d{2}:\d{2}$',
                ],
            ],
            'required' => ['id', 'file'],
        ];
    }

    protected function product001SchemaWithNull(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'is_admin' => ['type' => 'boolean'],
                'file' => ['type' => 'string', 'format' => 'binary',],
                'nullable_field' => ['type' => 'string', 'nullable' => true],
                'date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'nullable' => true,
                    'pattern' => '^\d{4}-\d{2}-\d{2}$',
                ],
                'time' => [
                    'type' => 'string',
                    'pattern' => '^\d{2}:\d{2}:\d{2}$',
                ],
            ],
            'required' => ['id', 'file'],
        ];
    }

    public function typeToArrayCases()
    {
        $product001Schema = $this->product001Schema();

        $product001SchemaWithNull = $this->product001SchemaWithNull();

        return [
            [
                Map001Type::class,
                [
                    'type' => 'object',
                    'example' => ['id' => 1, 'name' => 'INFO'],
                ],
                null,
            ],
            [
                Map002Type::class,
                [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'integer',
                    ],
                    'example' => ['优' => 90, '良' => 80, '中' => 60],
                ],
                null,
            ],
            [
                Map003Type::class,
                [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
                null,
            ],
            [
                'string',
                [
                    'type' => 'string',
                ],
                null,
            ],
            [
                ['string'],
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                null,
            ],

            [
                'string?',
                [
                    'type' => ['string', 'null'],
                ],
                [
                    'type' => 'string',
                    'nullable' => true,
                ],
            ],

            [
                ['string?'],
                [
                    'type' => 'array',
                    'items' => [
                        'type' => ['string', 'null'],
                    ],
                ],
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'nullable' => true,
                    ],
                ],
            ],

            [
                Enum001Type::class,
                [
                    'type' => 'string',
                    'enum' => [
                        'foo',
                        'bar',
                    ],
                ],
                [
                    'type' => 'string',
                    'enum' => [
                        'foo',
                        'bar',
                    ],
                ],
            ],

            [
                Product001Type::class,
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'is_admin' => ['type' => 'boolean'],
                        'file' => ['type' => 'string', 'format' => 'binary',],
                        'nullable_field' => ['type' => ['string', 'null']],
                        'date' => [
                            'type' => ['string', 'null'],
                            'format' => 'date',
                            'pattern' => '^\d{4}-\d{2}-\d{2}$',
                        ],
                        'time' => [
                            'type' => 'string',
                            'pattern' => '^\d{2}:\d{2}:\d{2}$',
                        ],
                    ],
                    'required' => ['id', 'file'],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'is_admin' => ['type' => 'boolean'],
                        'file' => ['type' => 'string', 'format' => 'binary',],
                        'nullable_field' => ['type' => 'string', 'nullable' => true],
                        'date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'nullable' => true,
                            'pattern' => '^\d{4}-\d{2}-\d{2}$',
                        ],
                        'time' => [
                            'type' => 'string',
                            'pattern' => '^\d{2}:\d{2}:\d{2}$',
                        ],
                    ],
                    'required' => ['id', 'file'],
                ],
            ],

            [
                Product002Type::class,
                [
                    'type' => 'object',
                    'properties' => [
                        'field1' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'field2' => [
                            'type' => 'array',
                            'items' => [
                                'type' => ['string', 'null'],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'field1' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'field2' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'nullable' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                Product005Type::class,
                [
                    'required' => ['related1'],
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'field1' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'field2' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => ['string', 'null'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'required' => ['related1'],
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'field1' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'field2' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                            'nullable' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                Product003Type::class,
                [
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'name' => ['type' => 'string'],
                                'is_admin' => ['type' => 'boolean'],
                                'file' => ['type' => 'string', 'format' => 'binary',],
                                'nullable_field' => ['type' => ['string', 'null']],
                                'date' => [
                                    'type' => ['string', 'null'],
                                    'format' => 'date',
                                    'pattern' => '^\d{4}-\d{2}-\d{2}$',
                                ],
                                'time' => [
                                    'type' => 'string',
                                    'pattern' => '^\d{2}:\d{2}:\d{2}$',
                                ],
                            ],
                            'required' => ['id', 'file'],

                        ],
                        'related2' => [
                            'type' => 'object',
                            'properties' => [
                                'field1' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'field2' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => ['string', 'null'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'name' => ['type' => 'string'],
                                'is_admin' => ['type' => 'boolean'],
                                'file' => ['type' => 'string', 'format' => 'binary',],
                                'nullable_field' => ['type' => 'string', 'nullable' => true],
                                'date' => [
                                    'type' => 'string',
                                    'format' => 'date',
                                    'nullable' => true,
                                    'pattern' => '^\d{4}-\d{2}-\d{2}$',
                                ],
                                'time' => [
                                    'type' => 'string',
                                    'pattern' => '^\d{2}:\d{2}:\d{2}$',
                                ],
                            ],
                            'required' => ['id', 'file'],

                        ],
                        'related2' => [
                            'type' => 'object',
                            'properties' => [
                                'field1' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'field2' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                        'nullable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                Product004Type::class,
                [
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'name' => ['type' => 'string'],
                                'is_admin' => ['type' => 'boolean'],
                                'file' => ['type' => 'string', 'format' => 'binary',],
                                'nullable_field' => ['type' => ['string', 'null']],
                                'date' => [
                                    'type' => ['string', 'null'],
                                    'format' => 'date',
                                    'pattern' => '^\d{4}-\d{2}-\d{2}$',
                                ],
                                'time' => [
                                    'type' => 'string',
                                    'pattern' => '^\d{2}:\d{2}:\d{2}$',
                                ],
                            ],
                            'required' => ['id', 'file'],

                        ],
                        'related2' => [
                            'oneOf' => [
                                [
                                    'type' => 'null',
                                ],
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'field1' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'field2' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => ['string', 'null'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => ['related1'],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'name' => ['type' => 'string'],
                                'is_admin' => ['type' => 'boolean'],
                                'file' => ['type' => 'string', 'format' => 'binary'],
                                'nullable_field' => ['type' => 'string', 'nullable' => true],
                                'date' => [
                                    'type' => 'string',
                                    'format' => 'date',
                                    'nullable' => true,
                                    'pattern' => '^\d{4}-\d{2}-\d{2}$',
                                ],
                                'time' => [
                                    'type' => 'string',
                                    'pattern' => '^\d{2}:\d{2}:\d{2}$',
                                ],
                            ],
                            'required' => ['id', 'file'],

                        ],
                        'related2' => [
                            'type' => 'object',
                            'properties' => [
                                'field1' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'field2' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                        'nullable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => ['related1'],
                ],
            ],

            [
                new class extends InputType {
                    public static $limit = 'query:!number';
                    public static $offset = 'query:number?';
                    public static $default_in = 'number';
                },
                [
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'number'],
                    ],
                    [
                        'name' => 'offset',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => ['number', 'null']],
                    ],
                    [
                        'name' => 'default_in',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'number'],
                    ],
                ],

                [
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'number'],
                    ],
                    [
                        'name' => 'offset',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'number', 'nullable' => true],
                    ],
                    [
                        'name' => 'default_in',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'number'],
                    ],
                ],
            ],

            [
                [Product001Type::class],
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                            'is_admin' => [
                                'type' => 'boolean',
                            ],
                            'file' => [
                                'type' => 'string',
                                'format' => 'binary',
                            ],
                            'nullable_field' => [
                                'type' => ['string', 'null'],
                            ],
                            'date' => [
                                'type' => ['string', 'null'],
                                'format' => 'date',
                                'pattern' => '^\d{4}-\d{2}-\d{2}$',
                            ],
                            'time' => [
                                'type' => 'string',
                                'pattern' => '^\d{2}:\d{2}:\d{2}$',
                            ],
                        ],
                        'required' => ['id', 'file'],
                    ],
                ],
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                            'is_admin' => [
                                'type' => 'boolean',
                            ],
                            'file' => [
                                'type' => 'string',
                                'format' => 'binary',
                            ],
                            'nullable_field' => [
                                'type' => 'string',
                                'nullable' => true,
                            ],
                            'date' => [
                                'type' => 'string',
                                'format' => 'date',
                                'nullable' => true,
                                'pattern' => '^\d{4}-\d{2}-\d{2}$',
                            ],
                            'time' => [
                                'type' => 'string',
                                'pattern' => '^\d{2}:\d{2}:\d{2}$',
                            ],
                        ],
                        'required' => ['id', 'file'],
                    ],
                ],
            ],
            [
                Union001Type::class,
                [
                    'oneOf' => [
                        [
                            'type' => 'string',
                        ],
                        [
                            'type' => 'integer',
                        ],
                        $product001Schema,
                    ],
                ],
                [
                    'oneOf' => [
                        [
                            'type' => 'string',
                        ],
                        [
                            'type' => 'integer',
                        ],
                        $product001SchemaWithNull,
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider typeToArrayCases
     * @param $type
     * @param $expect1
     * @param $expect2
     */
    public function testTypeToArray($type, $expect1, $expect2)
    {
        $parser = new TypeParser(TypeParser::MODE_JSON_SCHEMA);
        $this->assertEquals($expect1, $parser->parse($type));

        $parser = new TypeParser(TypeParser::MODE_OPEN_API);
        $this->assertEquals($expect2 ?? $expect1, $parser->parse($type));

        $parser = new TypeParser(TypeParser::MODE_OPEN_API | TypeParser::MODE_OPEN_API_31);
        $this->assertEquals($expect1, $parser->parse($type));
    }

    public function typeToArrayWithRefCases()
    {
        return [
            [
                Product002Type::class,
                [
                    '$ref' => '#/components/schemas/Product002',
                ],
                [
                    'Product002' => [
                        'type' => 'object',
                        'properties' => [
                            'field1' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'field2' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'nullable' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                Union001Type::class . '?',
                [
                    'oneOf' => [
                        [
                            'type' => 'string',
                        ],
                        [
                            'type' => 'integer',
                        ],
                        [
                            '$ref' => '#/components/schemas/Product001',
                        ],
                        [
                            'type' => 'null',
                        ],
                    ],
                ],
                [
                    'Product001' => $this->product001SchemaWithNull(),
                ]
            ],
        ];
    }

    /**
     * @dataProvider typeToArrayWithRefCases
     */
    public function testTypeToArrayWithRef($type, $expect, $schema)
    {
        $parser = new TypeParser(TypeParser::MODE_OPEN_API | TypeParser::MODE_REF_SCHEMA);

        $this->assertEquals($expect, $parser->parse($type));
        $this->assertEquals($schema, $parser->getSchemas());
    }

    public function inputDataCases()
    {
        return [
            // cast integer to bool values
            [
                [
                    'query' => ['a' => '1'],
                ],
                [
                    [
                        'name' => 'a',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                [],
                [
                    'query' => ['a' => true],
                ],
            ],
            [
                [
                    'query' => ['a' => '0'],
                ],
                [
                    [
                        'name' => 'a',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                [],
                [
                    'query' => ['a' => false],
                ],
            ],
            [
                [
                    'query' => ['a' => 1],
                ],
                [
                    [
                        'name' => 'a',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                [],
                [
                    'query' => ['a' => true],
                ],
            ],
            [
                [
                    'query' => ['a' => 0],
                ],
                [
                    [
                        'name' => 'a',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                [],
                [
                    'query' => ['a' => false],
                ],
            ],
            [
                [
                    'query' => ['a' => 'true'],
                ],
                [
                    [
                        'name' => 'a',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                [],
                [
                    'query' => ['a' => true],
                ],
            ],
            [
                [
                    'query' => ['a' => 'false'],
                ],
                [
                    [
                        'name' => 'a',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
                [],
                [
                    'query' => ['a' => false],
                ],
            ],
            // missing required input field
            [
                [
                    'query' => [],
                ],
                [
                    [
                        'name' => 'not_exist',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'number',
                        ],
                    ],
                ],
                ['The required query parameter: \'not_exist\' is required'],
                [],
            ],

            // missing optional input field
            [
                [
                    'query' => [],
                ],
                [
                    [
                        'name' => 'not_exist',
                        'in' => 'query',
                        'schema' => [
                            'type' => 'number',
                        ],
                    ],
                ],
                [],
                [],
            ],

            // the required field is present
            [
                [
                    'query' => ['foo' => 1],
                ],
                [
                    [
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'number',
                        ],
                    ],
                ],
                [],
                ['query' => ['foo' => 1]],
            ],

            // type casting succeed
            [
                [
                    'query' => ['foo' => '1'],
                ],
                [
                    [
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'number',
                        ],
                    ],
                ],
                [],
                ['query' => ['foo' => 1]],
            ],

            // type casting failed
            [
                [
                    'query' => ['foo' => 'bar'],
                ],
                [
                    [
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'number',
                        ],
                    ],
                ],
                ['The type of query parameter "foo" is invalid, string value found, but a number is required'],
                [],
            ],
        ];
    }

    /**
     * @dataProvider inputDataCases
     */
    public function testValidateInputData($inputs, $definition, $errors, $data)
    {
        $validator = new InputValidator($inputs);

        $validator->validate($definition);

        $this->assertEquals($errors, $validator->getErrors());
        $this->assertEquals($data, $validator->getData());
    }

    public function dataCases()
    {
        return [
            [
                '1',
                [
                    'type' => 'integer',
                ],
                ['String value found, but an integer is required'],
            ],

            [
                ['1'],
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
                ['The data of "[0]" is invalid, string value found, but an integer is required'],
            ],

            [
                [
                    'foo' => ['1'],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'foo' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
                ['The data of "foo[0]" is invalid, string value found, but an integer is required'],
            ],

            # validate nullable object type
            [
                [
                    'foo' => null,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'foo' => [
                            'oneOf' => [
                                ['type' => 'null'],
                            ],
                        ],
                    ],
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider dataCases
     */
    public function testValidateData($payload, $definition, $errors)
    {
        $validator = new TypeValidator();
        $result = $validator->validate($payload, $definition);

        $this->assertEquals($errors, $validator->getErrors());
        $this->assertEquals($errors ? false : true, $result);
    }
}

/**
 * A very sample product type.
 */
class Product001Type extends ProductType
{
    public static $id = '!integer';
    public static $name = 'string';
    public static $is_admin = 'boolean';
    public static $file = '!binary';
    public static $nullable_field = 'string?';
    public static $date = 'date?';
    public static $time = 'time';
}

/**
 * A product type with array.
 */
class Product002Type extends ProductType
{
    public static $field1 = ['string'];
    public static $field2 = ['string?'];
}

/**
 * A product type with custom type field.
 */
class Product003Type extends ProductType
{
    public static $related1 = Product001Type::class;
    public static $related2 = Product002Type::class;
}

/**
 * A product type with custom type field.
 */
class Product004Type extends ProductType
{
    public static $related1 = '!' . Product001Type::class;
    public static $related2 = Product002Type::class . '?';
}

/**
 * A product type with custom array type field.
 */
class Product005Type extends ProductType
{
    public static $related1 = '![' . Product002Type::class . ']';
}

class Enum001Type extends SumType
{
    public static function composite()
    {
        return [
            'foo',
            'bar',
        ];
    }
}

class Map001Type extends MapType
{
    public static function valueType(): string
    {
        return '';
    }

    public static function example(): array
    {
        return [
            'id' => 1,
            'name' => 'INFO',
        ];
    }
}

class Map002Type extends MapType
{
    public static function valueType(): string
    {
        return 'integer';
    }

    public static function example(): array
    {
        return ['优' => 90, '良' => 80, '中' => 60];
    }
}

class Dict003ItemType extends ProductType
{
    public static $name = 'string';
}

class Map003Type extends MapType
{
    public static function valueType(): string
    {
        return Dict003ItemType::class;
    }

    public static function example(): array
    {
        return [];
    }
}

class Union001Type extends UnionType
{
    public static function allowedTypes(): array
    {
        return [
            'string',
            'integer',
            Product001Type::class,
        ];
    }
}
