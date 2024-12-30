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

/**
 * @phpstan-type HeaderType array{headerName: string, possibleValue: array<string>}
 */
class OperationType
{
    #[SerializedName('@context')]
    public string $context = 'http://www.w3.org/ns/hydra/context.jsonld';

    /**
     * @var array<string>|string
     */
    #[SerializedName('@type')]
    public array|string $type = 'Operation';

    public ?string $title = null;
    public ?string $description = null;
    public string $method;
    public ?string $expects = null;
    public ?string $returns = null;

    /**
     * @var list<StatusType>
     */
    public array $possibleStatus = [];

    /**
     * @var list<string|HeaderType>
     */
    public array $returnsHeader = [];

    /**
     * @var list<string|HeaderType>
     */
    public array $expectsHeader = [];

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
        return $this->{$name} ?? $this->extraProperties[$name] ?? null;
    }
}
