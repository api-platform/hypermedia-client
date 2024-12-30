<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MyApp;

use ApiPlatform\HypermediaClient\ApiPlatformHypermediaClientBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return Bundle[]
     */
    public function registerBundles(): array
    {
        return [new FrameworkBundle(), new ApiPlatformHypermediaClientBundle()];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('api_platform_hypermedia_client', [
            'base_uri' => 'http://localhost:8080',
            'namespace' => '\\MyApp\\ApiResource',
            'directory' => 'tests/Fixtures',
            'path_name' => '/api/docs',
        ]);
    }

    public function shutdown(): void
    {
        parent::shutdown();
        restore_exception_handler();
    }
}
