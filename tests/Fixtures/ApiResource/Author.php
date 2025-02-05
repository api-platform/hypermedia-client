<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MyApp\ApiResource;

/**
 * @id schema:Person
 *
 * @property string             $givenName
 * @property string             $familyName
 * @property \DateTimeImmutable $birthDate
 *
 * @method \MyApp\ApiResource\Author                                           getAuthor(?array<string, mixed> $options = null)           Retrieves a Author resource.
 * @method \MyApp\ApiResource\Author                                           patchAuthor(?array<string, mixed> $options = null)         Updates the Author resource.
 * @method null                                                                deleteAuthor(?array<string, mixed> $options = null)        Deletes the Author resource.
 * @method \ApiPlatform\HypermediaClient\Collection<\MyApp\ApiResource\Author> getAuthorCollection(?array<string, mixed> $options = null) Retrieves the collection of Author resources.
 * @method \MyApp\ApiResource\Author                                           postAuthor(?array<string, mixed> $options = null)          Creates a Author resource.
 */
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getAuthor',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\MyApp\ApiResource\Author',
    uriTemplatePropertyPath: '@id'
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'patchAuthor',
    baseUri: 'http://localhost:8080',
    method: 'PATCH',
    input: '\MyApp\ApiResource\Author',
    output: '\MyApp\ApiResource\Author',
    uriTemplatePropertyPath: '@id',
    headers: ['content-type' => 'application/merge-patch+json']
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'deleteAuthor',
    baseUri: 'http://localhost:8080',
    method: 'DELETE',
    uriTemplatePropertyPath: '@id'
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getAuthorCollection',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\ApiPlatform\HypermediaClient\Collection<\MyApp\ApiResource\Author>',
    uriTemplate: '/api/authors'
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'postAuthor',
    baseUri: 'http://localhost:8080',
    method: 'POST',
    input: '\MyApp\ApiResource\Author',
    output: '\MyApp\ApiResource\Author',
    uriTemplate: '/api/authors'
)]
class Author extends \ApiPlatform\HypermediaClient\ApiResource
{
}
