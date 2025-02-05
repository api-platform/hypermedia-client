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
 * @id #Error
 *
 * @property string $id
 * @property string $title
 * @property string $detail
 * @property string $instance
 * @property string $type
 * @property mixed  $meta
 * @property mixed  $source
 * @property mixed  $trace
 * @property string $message
 * @property int    $code
 * @property string $file
 * @property int    $line
 * @property string $description
 * @property mixed  $previous
 *
 * @method \MyApp\ApiResource\Error getError(?array<string, mixed> $options = null) Retrieves a Error resource.
 */
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getError',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\MyApp\ApiResource\Error',
    uriTemplatePropertyPath: '@id'
)]
class Error extends \ApiPlatform\HypermediaClient\ApiResource
{
}
