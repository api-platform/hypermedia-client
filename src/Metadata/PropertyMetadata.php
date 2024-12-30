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

use ApiPlatform\HypermediaClient\Hydra\OperationType;

final class PropertyMetadata
{
    /**
     * @param list<OperationType> $operations
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $class = null,
        public readonly array $operations = [],
    ) {
    }
}
