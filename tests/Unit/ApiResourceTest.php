<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Tests\Unit;

use ApiPlatform\HypermediaClient\ApiResource;
use ApiPlatform\HypermediaClient\Exception\RuntimeException;
use ApiPlatform\HypermediaClient\Link;
use MyApp\ApiResource\Author;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiResource::class)]
#[CoversClass(Link::class)]
class ApiResourceTest extends TestCase
{
    public function testHasPhpDocProperties(): void
    {
        $apiResource = new Author();
        $this->assertEquals(
            [
                new PropertyTagValueNode(
                    new IdentifierTypeNode('string'),
                    '$givenName',
                    ''
                ),
                new PropertyTagValueNode(
                    new IdentifierTypeNode('string'),
                    '$familyName',
                    ''
                ),
                new PropertyTagValueNode(
                    new IdentifierTypeNode('\DateTimeImmutable'),
                    '$birthDate',
                    ''
                ),
            ],
            $apiResource->getProperties()
        );
    }

    public function testDoesNotHaveALink(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No link named doesNotExist found.');
        $apiResource = new Author();
        $apiResource->doesNotExist(); // @phpstan-ignore-line test magic method
    }
}
