<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Metadata\Factory;

use ApiPlatform\HypermediaClient\Collection;
use ApiPlatform\HypermediaClient\Hydra\ApiDocumentationType;
use ApiPlatform\HypermediaClient\Link;
use ApiPlatform\HypermediaClient\Metadata\AttributeMetadata;
use ApiPlatform\HypermediaClient\Metadata\ClassMetadata;
use ApiPlatform\HypermediaClient\Metadata\MethodMetadata;
use ApiPlatform\HypermediaClient\Metadata\PropertyMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClassMetadataFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @param array{baseUri: string, namespace: string, directory: string, pathName: string, httpClient?: HttpClientInterface, serializer?: SerializerInterface} $context
     *
     * @return ClassMetadata[]
     */
    public function create(array $context): array
    {
        ['baseUri' => $baseUri, 'namespace' => $namespace, 'directory' => $directory, 'pathName' => $pathName] = $context;

        /** @var ApiDocumentationType */
        $docs = ($context['serializer'] ?? $this->serializer)->deserialize(
            ($context['httpClient'] ?? $this->httpClient)->request('GET', $pathName)->getContent(),
            ApiDocumentationType::class,
            'jsonld'
        );

        /** @var array<string, ClassMetadata> */
        $classes = [];
        /** @var array<string, class-string> */
        $idsToClass = [];

        foreach ($docs->supportedClass as $cl) {
            $id = $this->toPHPCase($cl->title);
            $fqdn = $namespace.'\\'.$id;
            $classes[$fqdn] = new ClassMetadata(
                id: $cl->id,
                namespace: substr($namespace, 1),
                filePath: $this->getPSRFilePath($directory, $fqdn),
                name: $id,
                properties: [],
                methods: [],
                attributes: []
            );

            $idsToClass[$cl->id] = $fqdn;

            foreach ($cl->supportedOperation as $operation) {
                $headers = [];
                foreach ($operation->expectsHeader ?? [] as $header) {
                    if (isset($header['headerName'])) {
                        $headers[strtolower($header['headerName'])] = $header['possibleValue'][0];
                    }
                }

                $name = $operation->{'rdfs:label'} ?? $operation->title ?? $operation->method;

                $classes[$fqdn]->methods[(string) $name] = new MethodMetadata(
                    input: '?array<string, mixed>',
                    inputType: $this->getPhpType($operation->expects ?? null, $namespace, $classes, $fqdn, true),
                    output: $this->getPhpType($operation->returns ?? null, $namespace, $classes, $fqdn),
                    method: $operation->method ?? 'GET',
                    headers: $headers ?: null,
                    uri: 'Entrypoint' === $id ? $docs->entrypoint : null,
                    description: $operation->description ?? null
                );
            }
        }

        // 2nd pass for idsToClass
        foreach ($docs->supportedClass as $cl) {
            $id = $this->toPHPCase($cl->title);

            $fqdn = $namespace.'\\'.$id;
            foreach ($cl->supportedProperty as $property) {
                if (is_string($property->property) || !$property->property) {
                    continue;
                }

                $types = [];
                $classIndex = $isCollection = false;
                $class = null;
                foreach ((array) ($property->property->range ?? []) as $r) {
                    if (is_array($r)) {
                        if (in_array('Collection', $r, true) || in_array('hydra:Collection', $r, true)) {
                            $isCollection = true;
                        }

                        if (isset($r['owl:equivalentClass'])) {
                            if ($class = $idsToClass[$r['owl:equivalentClass']['owl:allValuesFrom']['@id']] ?? null) {
                                $types[] = $class;
                                $classIndex = key($types);
                            }
                        }

                        continue;
                    }

                    if ('Collection' === $r || 'hydra:Collection' === $r) {
                        $isCollection = true;
                        continue;
                    }

                    if ($class = $idsToClass[$r] ?? null) {
                        $types[] = $class;
                        $classIndex = key($types);
                        continue;
                    }

                    $types[] = match ($r) {
                        'xmls:integer' => 'integer',
                        'xsd:double', 'xsd:float' => 'float',
                        'xmls:boolean' => 'bool',
                        'xmls:string' => 'string',
                        'xmls:dateTime' => '\DateTimeImmutable',
                        default => 'string',
                    };
                }

                if ($isCollection && false !== $classIndex) {
                    $types[$classIndex] = '\\'.Collection::class.'<'.$types[$classIndex].'>';
                }

                if (!$types) {
                    $types[] = 'mixed';
                }

                $type = implode('|', $types);

                if ('Link' !== $property->property->type && 'hydra:Link' !== $property->property->type) {
                    $classes[$fqdn]->properties[$property->title] = new PropertyMetadata(type: $type);
                    continue;
                }

                if ('Entrypoint' === $id) {
                    $name = str_replace('#Entrypoint/', '', $property->property->id);
                } else {
                    $name = str_replace($cl->id.'/', '', $property->property->id);
                }

                $type = '\\'.Link::class.'<'.$type.'>';

                if (1 !== ($property->property->{'owl:maxCardinality'} ?? null)) {
                    $type = $type.'[]';
                }

                $classes[$fqdn]->properties[$name] = new PropertyMetadata(type: $type, class: $class, operations: $property->property->supportedOperation ?? []);
            }
        }

        if (isset($docs->entrypoint)) {
            // Fetch entrypoint, add methods on found classes
            $entrypoint = ($context['httpClient'] ?? $this->httpClient)->request('GET', $docs->entrypoint)->toArray();
            foreach ($entrypoint as $key => $value) {
                if (!isset($classes[$namespace.'\\Entrypoint']->properties[$key])) {
                    continue;
                }

                $property = $classes[$namespace.'\\Entrypoint']->properties[$key];
                if (($fqdn = $property->class) && isset($classes[$fqdn])) {
                    foreach ($property->operations as $operation) {
                        $name = $operation->{'rdfs:label'} ?? $operation->title ?? $operation->method;
                        $classes[$fqdn]->methods[(string) $name] = new MethodMetadata(
                            input: '?array<string, mixed>',
                            inputType: $this->getPhpType($operation->expects ?? null, $namespace, $classes, $fqdn, true),
                            output: $this->getPhpType($operation->returns ?? null, $namespace, $classes, $fqdn),
                            method: $operation->method ?? 'GET',
                            uri: $value,
                            description: $operation->description ?? null
                        );
                    }
                }
            }
        }

        // Prepare class attributes, for now only Link is used
        foreach ($classes as $fqdn => $class) {
            foreach ($class->methods as $name => $method) {
                $args = [
                    'name' => $name,
                    'baseUri' => $baseUri,
                    'method' => $method->method,
                ];

                if ($method->inputType) {
                    $args['input'] = $method->inputType;
                }

                if ('null' !== $method->output) {
                    $args['output'] = $method->output;
                }

                if ($uri = $method->uri) {
                    $args['uriTemplate'] = $uri;
                } else {
                    $args['uriTemplatePropertyPath'] = '@id';
                }

                if ($method->headers) {
                    $args['headers'] = $method->headers;
                }

                $classes[$fqdn]->attributes[] = new AttributeMetadata(
                    className: '\\'.Link::class,
                    arguments: $args
                );
            }
        }

        return $classes;
    }

    private function toPHPCase(string $str): string
    {
        return preg_replace_callback(
            '/(^|_|\.)+(.)/',
            fn ($match) => ('.' === $match[1] ? '_' : '').strtoupper($match[2]),
            str_replace(' ', '_', $str)
        );
    }

    /**
     * @param array<string, ClassMetadata> $classes
     */
    private function getPhpType(?string $rdfType, string $namespace, array $classes, string $currentClassName, bool $input = false): ?string
    {
        if (null === $rdfType) {
            return $input ? null : 'void';
        }

        if ('owl:Nothing' === $rdfType) {
            return 'null';
        }

        if ('Collection' === $rdfType || 'hydra:Collection' === $rdfType) {
            return '\\'.Collection::class.'<'.$currentClassName.'>';
        }

        $cl = $namespace.'\\'.$rdfType;
        if (!array_key_exists($cl, $classes)) {
            return $rdfType;
        }

        return $cl;
    }

    private function getPSRFilePath(string $directory, string $fqdn): string
    {
        $parts = explode('\\', $fqdn);
        if (str_starts_with($fqdn, '\\')) {
            array_shift($parts);
        }
        array_shift($parts);
        array_unshift($parts, $directory);

        return implode(DIRECTORY_SEPARATOR, $parts).'.php';
    }
}
