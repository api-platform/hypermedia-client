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
 * @id #Book
 *
 * @property \ApiPlatform\HypermediaClient\Link<Author>[] $authors
 * @property \ApiPlatform\HypermediaClient\Link<Author>   $author
 *
 * @method \MyApp\ApiResource\Book                                           getBook(?array<string, mixed> $options = null)           Retrieves a Book resource.
 * @method \MyApp\ApiResource\Book                                           patchBook(?array<string, mixed> $options = null)         Updates the Book resource.
 * @method null                                                              deleteBook(?array<string, mixed> $options = null)        Deletes the Book resource.
 * @method \ApiPlatform\HypermediaClient\Collection<\MyApp\ApiResource\Book> getBookCollection(?array<string, mixed> $options = null) Retrieves the collection of Book resources.
 * @method \MyApp\ApiResource\Book                                           postBook(?array<string, mixed> $options = null)          Creates a Book resource.
 */
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getBook',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\MyApp\ApiResource\Book',
    uriTemplatePropertyPath: '@id'
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'patchBook',
    baseUri: 'http://localhost:8080',
    method: 'PATCH',
    input: '\MyApp\ApiResource\Book',
    output: '\MyApp\ApiResource\Book',
    uriTemplatePropertyPath: '@id',
    headers: ['content-type' => 'application/merge-patch+json']
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'deleteBook',
    baseUri: 'http://localhost:8080',
    method: 'DELETE',
    uriTemplatePropertyPath: '@id'
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getBookCollection',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\ApiPlatform\HypermediaClient\Collection<\MyApp\ApiResource\Book>',
    uriTemplate: '/api/books'
)]
#[\ApiPlatform\HypermediaClient\Link(
    name: 'postBook',
    baseUri: 'http://localhost:8080',
    method: 'POST',
    input: '\MyApp\ApiResource\Book',
    output: '\MyApp\ApiResource\Book',
    uriTemplate: '/api/books'
)]
class Book extends \ApiPlatform\HypermediaClient\ApiResource
{
}
