<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Tests\Unit;

use ApiPlatform\HypermediaClient\ApiResource;
use ApiPlatform\HypermediaClient\Collection;
use ApiPlatform\HypermediaClient\Exception\RuntimeException;
use ApiPlatform\HypermediaClient\Link;
use MyApp\ApiResource\Author;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(Collection::class)]
#[CoversClass(ApiResource::class)]
#[CoversClass(Link::class)]
class LinkTest extends TestCase
{
    public function testNoUri(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No URI.');
        $apiResource = new Author();
        $apiResource->getAuthor();
    }

    public function testUri(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            '@context' => '/api/contexts/Author',
            '@id' => '/api/authors',
            '@type' => 'Collection',
            'totalItems' => 1,
            'member' => [[
                '@id' => '/api/authors/1',
                '@type' => 'schema:Person',
                'id' => 1,
                'givenName' => 'Soyuka',
                'familyName' => 'Arakusa',
                'birthDate' => '2023-10-25T00=>00=>00+00=>00',
            ]],
            'view' => [
                '@id' => '/api/authors.jsonld?itemsPerPage=10&page=1',
                '@type' => 'PartialCollectionView',
                'first' => '/api/authors.jsonld?itemsPerPage=10&page=1',
                'last' => '/api/authors.jsonld?itemsPerPage=10&page=12',
                'next' => '/api/authors.jsonld?itemsPerPage=10&page=2',
            ],
        ]);
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->with('GET', '/api/authors', [
            'headers' => [
                'accept' => 'application/ld+json',
            ],
        ])->willReturn($response);

        $apiResource = new Author();
        $collection = $apiResource->getAuthorCollection(['client' => $client]);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(1, $collection);

        $author = $collection[0];
        $this->assertInstanceOf(ApiResource::class, $author);
        $this->assertInstanceOf(Author::class, $author);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            '@id' => '/api/authors/1',
            '@context' => '/api/contexts/Author',
            '@type' => 'schema:Person',
            'id' => 1,
            'givenName' => 'Soyuka',
            'familyName' => 'Noname',
            'birthDate' => '2023-10-25T00=>00=>00+00=>00',
        ]);
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->with('GET', '/api/authors/1', [
            'headers' => [
                'accept' => 'application/ld+json',
            ],
        ])->willReturn($response);

        $author = $author->getAuthor(['client' => $client]);
        $this->assertInstanceOf(Author::class, $author);
        $this->assertEquals('Noname', $author->familyName);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            '@id' => '/api/authors/1',
            '@context' => '/api/contexts/Author',
            '@type' => 'schema:Person',
            'id' => 1,
            'givenName' => 'Soyuka',
            'familyName' => 'Noname',
            'birthDate' => '2023-10-25T00=>00=>00+00=>00',
        ]);
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->with('PATCH', '/api/authors/1', [
            'headers' => [
                'content-type' => 'application/merge-patch+json',
            ],
            'json' => ['givenName' => 'Soyuka'],
        ])->willReturn($response);

        $serializer = $this->createMock(NormalizerInterface::class);
        $serializer->method('normalize')->with($author, 'json')->willReturn(['givenName' => 'Soyuka']);

        $author = $author->patchAuthor(['client' => $client, 'serializer' => $serializer]);
        $this->assertInstanceOf(Author::class, $author);
    }
}
