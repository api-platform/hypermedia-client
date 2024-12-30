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

class ClassMetadata
{
    /**
     * @param array<string, PropertyMetadata> $properties
     * @param array<string, MethodMetadata>   $methods
     * @param AttributeMetadata[]             $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $namespace,
        public readonly string $name,
        public readonly string $filePath,
        public array $properties,
        public array $methods,
        public array $attributes,
    ) {
    }
}
