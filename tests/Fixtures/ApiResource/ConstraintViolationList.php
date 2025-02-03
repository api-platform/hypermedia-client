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
 * @id #ConstraintViolationList
 *
 * @property string $propertyPath
 * @property string $message
 */
class ConstraintViolationList extends \ApiPlatform\HypermediaClient\ApiResource
{
}
