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
 * @id #Entrypoint
 *
 * @property \ApiPlatform\HypermediaClient\Link<\ApiPlatform\HypermediaClient\Collection<Author>> $author
 * @property \ApiPlatform\HypermediaClient\Link<\ApiPlatform\HypermediaClient\Collection<Book>>   $book
 *
 * @method \MyApp\ApiResource\Entrypoint index(?array<string, mixed> $options = null)
 */
#[\ApiPlatform\HypermediaClient\Link(
    name: 'index',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\MyApp\ApiResource\Entrypoint',
    uriTemplate: '/api'
)]
class Entrypoint extends \ApiPlatform\HypermediaClient\ApiResource
{
}
