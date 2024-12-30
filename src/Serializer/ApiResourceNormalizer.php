<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Serializer;

use ApiPlatform\HypermediaClient\ApiResource;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiResourceNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    private NormalizerInterface $normalizer;

    /**
     * @param ApiResource          $object
     * @param array<string, mixed> $context
     *
     * @return \ArrayObject<int|string, mixed>|array<int|string, mixed>|string|int|float|bool|null
     */
    public function normalize($object, ?string $format = null, array $context = []): \ArrayObject|array|string|int|float|bool|null
    {
        /** @var array<string, mixed> */
        $res = [];
        foreach ($object->getProperties() as $property) {
            $propertyName = str_replace('$', '', $property->propertyName);
            $res[$propertyName] = $this->normalizer->normalize($object->{$propertyName}, $format, $context);
        }

        return $res;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ApiResource;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }
}
