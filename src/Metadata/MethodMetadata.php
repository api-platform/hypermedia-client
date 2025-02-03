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

final class MethodMetadata
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        public readonly string $output,
        public readonly string $method,
        public readonly ?string $input = null,
        public readonly ?string $inputType = null,
        public readonly ?string $uri = null,
        public readonly ?array $headers = [],
    ) {
    }
}
