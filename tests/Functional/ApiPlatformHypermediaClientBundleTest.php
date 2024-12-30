<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Tests\Functional;

use ApiPlatform\HypermediaClient\ApiPlatformHypermediaClientBundle;
use MyApp\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiPlatformHypermediaClientBundle::class)]
class ApiPlatformHypermediaClientBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $kernel = new Kernel('dev', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->assertTrue($container->hasParameter('api_platform.hypermedia_client.base_uri'));
        $this->assertSame($container->getParameter('api_platform.hypermedia_client.base_uri'), 'http://localhost:8080');
        $this->assertTrue($container->hasParameter('api_platform.hypermedia_client.namespace'));
        $this->assertSame($container->getParameter('api_platform.hypermedia_client.namespace'), '\\MyApp\\ApiResource');
        $this->assertTrue($container->hasParameter('api_platform.hypermedia_client.directory'));
        $this->assertSame($container->getParameter('api_platform.hypermedia_client.directory'), 'tests/Fixtures');
        $kernel->shutdown();
    }
}
