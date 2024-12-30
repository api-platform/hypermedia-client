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
 * @id #ConstraintViolation
 *
 * @property int        $status
 * @property string     $errorTitle
 * @property string     $violations
 * @property string     $detail
 * @property string     $message
 * @property string|int $code
 * @property string     $file
 * @property int        $line
 * @property string     $description
 * @property string     $type
 * @property string     $title
 * @property string     $instance
 * @property int        $statusCode
 * @property mixed      $headers
 * @property mixed      $trace
 * @property mixed      $previous
 *
 * @method \MyApp\ApiResource\ConstraintViolation getConstraintViolation(?array<string, mixed> $options = null)
 */
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getConstraintViolation',
    baseUri: 'http://localhost:8080',
    method: 'GET',
    output: '\MyApp\ApiResource\ConstraintViolation',
    uriTemplatePropertyPath: '@id'
)]
class ConstraintViolation extends \ApiPlatform\HypermediaClient\ApiResource
{
}
