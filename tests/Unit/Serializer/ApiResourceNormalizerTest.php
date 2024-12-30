<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Tests\Unit\Serializer;

use ApiPlatform\HypermediaClient\ApiResource;
use ApiPlatform\HypermediaClient\Serializer\ApiResourceNormalizer;
use MyApp\ApiResource\Author;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[CoversClass(ApiResourceNormalizer::class)]
class ApiResourceNormalizerTest extends TestCase
{
    public function testSupports(): void
    {
        $normalizer = new ApiResourceNormalizer();
        $this->assertTrue($normalizer->supportsNormalization(new Author()));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalize(): void
    {
        $apiResource = $this->createMock(ApiResource::class);
        $apiResource->method('getProperties')->willReturn([
            new PropertyTagValueNode(
                new IdentifierTypeNode('string'),
                '$givenName',
                ''
            ),
        ]);
        $apiResource->method('__get')->with('givenName')->willReturn('hello');

        $normalizerMock = $this->createMock(NormalizerInterface::class);
        $normalizerMock->method('normalize')->with('hello')->willReturn('hello');

        $normalizer = new ApiResourceNormalizer();
        $normalizer->setNormalizer($normalizerMock);

        $this->assertEquals(['givenName' => 'hello'], $normalizer->normalize($apiResource));
    }
}
