<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Metadata;

final class AttributeMetadata
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public readonly string $className,
        public readonly array $arguments,
    ) {
    }
}
