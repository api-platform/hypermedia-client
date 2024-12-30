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

class ApiDocumentationType
{
    #[SerializedName('@type')]
    public string $type = 'ApiDocumentation';

    #[SerializedName('@id')]
    public string $id;

    /**
     * @var array<mixed>
     */
    #[SerializedName('@context')]
    public array $context;
    public string $title;
    public string $entrypoint;

    /**
     * @var list<ClassType>
     */
    public array $supportedClass = [];

    /**
     * @var list<StatusType>
     */
    public array $possibleStatus = [];
}
