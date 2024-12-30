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

class ClassType
{
    #[SerializedName('@context')]
    public string $context = 'http://www.w3.org/ns/hydra/context.jsonld';

    /**
     * @var array<string>|string
     */
    #[SerializedName('@type')]
    public string|array $type = 'Class';

    #[SerializedName('@id')]
    public string $id;

    public ?string $title;
    public ?string $description;
    public ?string $domain;

    /**
     * @var list<PropertyType>
     */
    public array $supportedProperty = [];

    /**
     * @var list<OperationType>
     */
    public array $supportedOperation = [];

    /**
     * @var array<string, mixed>
     */
    private array $extraProperties = [];

    public function __set(string $name, mixed $value)
    {
        $this->extraProperties[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->extraProperties[$name] ?? null;
    }
}
