<?php

namespace rethink\typedphp;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use rethink\typedphp\security\ScopeInterface;
use const PREG_SPLIT_NO_EMPTY;

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
            'security' => $this->buildSecurity($apiClass),
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

        $contentType = $this->parseContentType($class) ?? 'application/json';

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

    private function parseContentType(\ReflectionClass $class)
    {
        $comment = $class->getDocComment();
        if ($comment) {
            $docblock = DocBlockFactory::createInstance()->create($comment);
            $tags = $docblock->getTagsByName('content-type');
            if (count($tags)) {
                return trim((string)$tags[0]->getDescription());
            }
        }
        return null;
    }

    /**
     * @param $apiClass
     * @return array
     * @link https://spec.openapis.org/oas/v3.0.1#security-requirement-object
     */
    protected function buildSecurity($apiClass): array
    {
        if (method_exists($apiClass, 'scopes') === false) {
            return [];
        }

        $scopes = $apiClass::scopes();

        // supported format:
        // 1. Scope
        // 2. [Scope1, Scope2] AND relation
        // 3. [[Scope1, Scope2], [Scope3, Scope4]] OR relation

        // only 1 scope
        if ($scopes instanceof ScopeInterface) {
            return [
                [
                    $scopes->schemeName() => [
                        $scopes->name(),
                    ],
                ]
            ];
        }

        if (is_array($scopes)) {
            // multiple scopes with AND relation
            if (current($scopes) instanceof ScopeInterface) {
                return [
                    $this->buildForAndScopes(...$scopes)
                ];
            }
            // multiple scopes with OR relation
            if (is_array(current($scopes))) {
                $security = [];
                foreach ($scopes as $scope) {
                    $security[] = $this->buildForAndScopes(...$scope);
                }
                return $security;
            }
        }

        throw new InvalidArgumentException('Invalid scopes definition.');
    }

    protected function buildForAndScopes(ScopeInterface ...$scopes): array
    {
        $result = [];
        foreach ($scopes as $scope) {
            $result[$scope->schemeName()][] = $scope->name();
        }
        return $result;
    }

    protected function buildResponses($apiClass, \ReflectionClass $class)
    {
        $responses = [];

        foreach ($apiClass::responses() as $code => $responseDefinition) {

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
            'securitySchemes' => (object)$this->buildSecuritySchemes(),
        ];
    }

    /**
     * @return array
     * @link https://spec.openapis.org/oas/v3.0.1#security-scheme-object
     */
    protected function buildSecuritySchemes(): array
    {
        $scopes = [];
        foreach ($this->apiClasses as $apiClass) {
            if (method_exists($apiClass, 'scopes')) {
                $scopes[] = $this->collectScopes($apiClass::scopes());
            }
        }
        $scopes = array_merge([], ...$scopes);

        $securitySchemes = [];
        /** @var ScopeInterface $scope */
        foreach ($scopes as $scope) {
            if (isset($securitySchemes[$scope->schemeName()])) {
                $securitySchemes[$scope->schemeName()]['flows']['clientCredentials']['scopes'][$scope->name()] = $scope->description();
            } else {
                $securitySchemes[$scope->schemeName()] = [
                    'type' => 'oauth2',
                    'flows' => [
                        'clientCredentials' => [
                            'tokenUrl' => '',
                            'scopes' => [
                                $scope->name() => $scope->description(),
                            ],
                        ],
                    ],
                ];
            }
        }
        return $securitySchemes;
    }

    protected function collectScopes($scopes): array
    {
        if ($scopes instanceof ScopeInterface) {
            return [$scopes];
        }
        if (is_array($scopes)) {
            $result = [];
            foreach ($scopes as $scope) {
                $result[] = $this->collectScopes($scope);
            }
            return array_merge([], ...$result);
        }
        throw new InvalidArgumentException('Invalid scopes definition.');
    }
}
