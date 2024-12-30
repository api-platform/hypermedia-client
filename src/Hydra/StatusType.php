<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Hydra;

use Symfony\Component\Serializer\Attribute\SerializedName;

class StatusType
{
    #[SerializedName('@context')]
    public string $context = 'http://www.w3.org/ns/hydra/context.jsonld';
    #[SerializedName('@type')]
    public string $type = 'Status';
    public string $statusCode;
}
