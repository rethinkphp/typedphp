<?php

namespace typedphp\tests;


use rethink\typedphp\InputValidator;
use rethink\typedphp\TypeParser;
use rethink\typedphp\types\InputType;
use rethink\typedphp\types\ProductType;
use rethink\typedphp\TypeValidator;
use PHPUnit\Framework\TestCase;

/**
 * Class TypeTest
 *
 * @package typedphp
 */
class TypeTest extends TestCase
{
    public function typeToArrayCases()
    {
        return [
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
                Product001Type::class,
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'nullable_field' => ['type' => ['string', 'null']],
                    ],
                    'required' => ['id'],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'nullable_field' => ['type' => 'string', 'nullable' => true],
                    ],
                    'required' => ['id'],
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
                    'required' => [],
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
                    'required' => [],
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
                                'nullable_field' => ['type' => ['string', 'null']],
                            ],
                            'required' => ['id'],

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
                            'required' => [],
                        ],
                    ],
                    'required' => [],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'related1' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer'],
                                'name' => ['type' => 'string'],
                                'nullable_field' => [ 'type' => 'string', 'nullable' => true],
                            ],
                            'required' => ['id'],

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
                            'required' => [],
                        ],
                    ],
                    'required' => [],
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
                    ]
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
                    ]
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
                            'nullable_field' => [
                                'type' => ['string', 'null']
                            ],
                        ],
                        'required' => ['id'],
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
                            'nullable_field' => [
                                'type' => 'string',
                                'nullable' => true,
                            ],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ]

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
                        'required' => [],
                    ],
                ],
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
                    ]
                ],
                ['The required query parameter: \'not_exist\' is required'],
                []
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
                    ]
                ],
                [],
                []
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
                    ]
                ],
                [],
                ['foo' => 1]
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
                    ]
                ],
                [],
                ['foo' => 1]
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
                    ]
                ],
                ['The type of query parameter "foo" is invalid, string value found, but a number is required'],
                []
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
                    ]
                ],
                ['The data of "[0]" is invalid, string value found, but an integer is required'],
            ],

            [
                [
                    'foo' => ['1']
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'foo' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'integer',
                            ]
                        ],
                    ]
                ],
                ['The data of "foo[0]" is invalid, string value found, but an integer is required'],
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
    public static $nullable_field = 'string?';
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
