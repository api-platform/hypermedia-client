<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Tests\Unit\Metadata\Factory;

use ApiPlatform\HypermediaClient\Hydra\ApiDocumentationType;
use ApiPlatform\HypermediaClient\Hydra\ClassType;
use ApiPlatform\HypermediaClient\Hydra\OperationType;
use ApiPlatform\HypermediaClient\Hydra\PropertyType;
use ApiPlatform\HypermediaClient\Link;
use ApiPlatform\HypermediaClient\Metadata\AttributeMetadata;
use ApiPlatform\HypermediaClient\Metadata\ClassMetadata;
use ApiPlatform\HypermediaClient\Metadata\Factory\ClassMetadataFactory;
use ApiPlatform\HypermediaClient\Metadata\MethodMetadata;
use ApiPlatform\HypermediaClient\Metadata\PropertyMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(ClassMetadataFactory::class)]
#[CoversClass(ApiDocumentationType::class)]
#[CoversClass(ClassType::class)]
#[CoversClass(PropertyType::class)]
#[CoversClass(OperationType::class)]
#[CoversClass(AttributeMetadata::class)]
#[CoversClass(ClassMetadata::class)]
#[CoversClass(MethodMetadata::class)]
#[CoversClass(PropertyMetadata::class)]
class ClassMetadataFactoryTest extends TestCase
{
    public function testEmptyClassMetadata(): void
    {
        $apiDocumentationType = new ApiDocumentationType();

        $json = '{}';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($json);
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->with('GET', '/')->willReturn($response);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->with($json, ApiDocumentationType::class, 'jsonld')->willReturn($apiDocumentationType);

        $classMetadataFactory = new ClassMetadataFactory($httpClient, $serializer);
        $data = $classMetadataFactory->create([
            'baseUri' => 'https://localhost',
            'namespace' => 'App\\ApiResource',
            'directory' => './src',
            'pathName' => '/',
        ]);

        $this->assertCount(0, $data);
    }

    public function testClassMetadata(): void
    {
        $prop = new PropertyType();
        $prop->property = new ClassType();
        $prop->property->type = 'rdf:Property';
        $prop->property->id = 'schema:givenName';
        $prop->property->domain = 'schema:Person';
        $prop->property->label = 'givenName'; // @phpstan-ignore-line
        $prop->property->range = 'xmls:string'; // @phpstan-ignore-line
        $prop->title = 'givenName';
        $prop->required = true;
        $prop->readable = true;
        $prop->writable = true;

        $operation = new OperationType();
        $operation->type = 'Operation';
        $operation->title = 'getAuthor';
        $operation->description = 'Updates the Author resource.';
        $operation->method = 'PATCH';
        $operation->expects = 'Author';
        $operation->returns = 'Author';
        $operation->expectsHeader = [
            [
                'headerName' => 'Content-Type',
                'possibleValue' => ['application/merge-patch+json'],
            ],
        ];

        $cl = new ClassType();
        $cl->id = 'schema:Person';
        $cl->title = 'Author';
        $cl->supportedProperty = [$prop];
        $cl->supportedOperation = [$operation];

        $apiDocumentationType = new ApiDocumentationType();
        $apiDocumentationType->id = '/api/docs.jsonld';
        $apiDocumentationType->title = 'Hello API Platform';
        $apiDocumentationType->entrypoint = '/api';
        $apiDocumentationType->supportedClass = [
            $cl,
        ];

        $json = '{}';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($json);

        $entrypointResponse = $this->createMock(ResponseInterface::class);
        $entrypointResponse->method('toArray')->willReturn(['author' => '/api/authors']);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturnMap(
                [
                    ['GET', '/', $response],
                    ['GET', $apiDocumentationType->entrypoint, $entrypointResponse],
                ]
            );

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->with($json, ApiDocumentationType::class, 'jsonld')->willReturn($apiDocumentationType);

        $classMetadataFactory = new ClassMetadataFactory($httpClient, $serializer);
        $data = $classMetadataFactory->create([
            'baseUri' => 'https://localhost',
            'namespace' => '\\App\\ApiResource',
            'directory' => './src',
            'pathName' => '/',
        ]);

        $this->assertArrayHasKey('\\App\\ApiResource\\Author', $data);
        $author = $data['\\App\\ApiResource\\Author'];
        $this->assertEquals($author, new ClassMetadata(
            id: 'schema:Person',
            namespace: 'App\\ApiResource',
            name: 'Author',
            filePath: './src/ApiResource/Author.php',
            properties: [
                'givenName' => new PropertyMetadata(type: 'string'),
            ],
            methods: [
                'getAuthor' => new MethodMetadata(
                    output: '\\App\\ApiResource\\Author',
                    method: 'PATCH',
                    input: '?array<string, mixed>',
                    inputType: '\\App\\ApiResource\\Author',
                    uri: null,
                    headers: ['content-type' => 'application/merge-patch+json']
                ),
            ],
            attributes: [
                new AttributeMetadata(
                    className: '\\'.Link::class,
                    arguments: [
                        'name' => 'getAuthor',
                        'baseUri' => 'https://localhost',
                        'method' => 'PATCH',
                        'input' => '\\App\\ApiResource\\Author',
                        'output' => '\\App\\ApiResource\\Author',
                        'uriTemplatePropertyPath' => '@id',
                        'headers' => [
                            'content-type' => 'application/merge-patch+json',
                        ],
                    ]
                ),
            ]
        ));
    }
}
