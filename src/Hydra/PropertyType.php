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

class PropertyType
{
    #[SerializedName('@context')]
    public string $context = 'http://www.w3.org/ns/hydra/context.jsonld';

    /**
     * @var array<string>|string
     */
    #[SerializedName('@type')]
    public string|array $type = 'Property';

    public string|ClassType|null $property;
    public ?string $title;
    public bool $required = false;
    public bool $readable = true;
    public bool $writable = true;
}
