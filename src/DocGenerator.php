<?php

namespace rethink\typedphp;

use ReflectionClass;
use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock;

/**
 * Class DocGenerator
 *
 * @package rethink\typedphp;
 */
class DocGenerator
{
    protected $apiClasses = [];
    /**
     * @var TypeParser
     */
    protected $parser;

    /**
     * Generator constructor.
     *
     * @param array $apiClasses
     * @param TypeParser|null $parser
     */
    public function __construct(array $apiClasses, $parser = null)
    {
        $this->apiClasses = $apiClasses;

        if ($parser) {
            $this->parser = $parser;
        } else {
            $this->parser = new TypeParser(TypeParser::MODE_OPEN_API | TypeParser::MODE_REF_SCHEMA);
        }
    }

    public function buildApiObject($apiClass)
    {
        if (!is_subclass_of($apiClass, ApiInterface::class)) {
            throw new InvalidArgumentException('An API class should be a subclass of ' . ApiInterface::class);
        }

        $class = new ReflectionClass($apiClass);

        $path = $this->getStaticProperty($class, 'path');
        $verb = $this->getStaticProperty($class, 'verb');

        $comment = $class->getDocComment();
        if ($comment) {
            $docblock = DocBlockFactory::createInstance()->create($comment);
        } else {
            $docblock = null;
        }
        $parameters = $apiClass::parameters();

        $object = [
            'summary' => $docblock ? $docblock->getSummary() : '',
            'description' => $docblock ? $docblock->getDescription()->render() : '',
            'tags' => $docblock ? $this->getDocTags($docblock) : [],
            'operationId' => $this->getStaticProperty($class, 'op'),
            'parameters' => $parameters ? $this->parser->parse($parameters) : [],
            'responses' => (object)$this->buildResponses($apiClass, $class),
        ];

        if ($bodyDefinition = $this->buildRequestBody($apiClass, $class)) {
            $object['requestBody'] = $bodyDefinition;
        }

        return [$path, strtolower($verb), $object];
    }

    protected function getStaticProperty(ReflectionClass $class, $property)
    {
        $className = $class->getName();

        if (!$class->hasProperty($property) || !($value = $class->getStaticPropertyValue($property))) {
            throw new InvalidArgumentException("Class $className requires the static property: '$property' to be declared and not empty.");
        }

        return $value;
    }

    protected function buildRequestBody($apiClass, \ReflectionClass $class)
    {
        $bodyDefinition = $apiClass::requestBody();

        if (!$bodyDefinition) {
            return;
        }

        $comment = $class->getMethod('requestBody')->getDocComment();

        $description = '';

        if ($comment) {
            $docblock = DocBlockFactory::createInstance()->create($comment);
            $description = trim($docblock->getSummary() . "\n\n" . $docblock->getDescription()->render());
        }

        $contentType = 'application/json';
        if (is_string($bodyDefinition) || is_object($bodyDefinition)) {
            $contentType = $bodyDefinition::contentType();
        }

        return [
            'description' => $description,
            'content' => [
                $contentType => [
                    'schema' => $this->parser->parse($bodyDefinition),
                ],
            ],
            'required' => true,
        ];
    }

    protected function buildResponses($apiClass, \ReflectionClass $class)
    {
        $responses = [];

        foreach ($apiClass::responses() as $code => $responseDefinition)  {

            if ($responseDefinition !== null) {
                $responses[$code] = [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => $this->parser->parse($responseDefinition),
                        ]
                    ],
                ];
            } else {
                $responses[$code] = [
                    'description' => 'No Content',
                ];
            }
        }

        return $responses;
    }

    protected function getDocTags(DocBlock $docblock)
    {
        $results = [];
        $tags = $docblock->getTagsByName('tags');

        foreach ($tags as $tag) {
            $results = array_merge(
                $results,
                preg_split('/,\s*/', (string)$tag->getDescription(), -1, PREG_SPLIT_NO_EMPTY)
            );
        }

        return $results;
    }

    protected function buildPathsObject()
    {
        foreach ($this->apiClasses as $apiClass) {
            list($path, $verb, $object) = $this->buildApiObject($apiClass);
            $paths[$path][$verb] = $object;
        }

        return (object)$paths;
    }

    /**
     * Generate segments for OpenAPI 3.0.
     *
     * @return array
     */
    public function generate()
    {
        return [
            'paths' => (object)$this->buildPathsObject(),
            'schemas' => (object)$this->parser->getSchemas(),
        ];
    }
}
