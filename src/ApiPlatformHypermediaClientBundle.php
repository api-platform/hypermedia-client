<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient;

use ApiPlatform\HypermediaClient\Command\FetchDocumentationCommand;
use ApiPlatform\HypermediaClient\Dumper\ClassMetadataDumper;
use ApiPlatform\HypermediaClient\Metadata\Factory\ClassMetadataFactory as HydraClassMetadataFactory;
use ApiPlatform\HypermediaClient\Serializer\HydraArrayDenormalizer;
use ApiPlatform\HypermediaClient\Serializer\HydraObjectDenormalizer;
use ApiPlatform\Serializer\JsonEncoder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class ApiPlatformHypermediaClientBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
            ->scalarNode('base_uri')->defaultValue('https://demo.api-platform.com')->end()
            ->scalarNode('namespace')->defaultValue('\\MyApp\\ApiResource')->end()
            ->scalarNode('directory')->defaultValue('src/ApiResource')->end()
            ->scalarNode('path_name')->defaultValue('/docs')->end()
            ->end()
        ;
    }

    /**
     * @param array{'base_uri': string, 'namespace': string, 'directory': string, 'path_name': string} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $parameters = $container->parameters();
        $parameters->set('api_platform.hypermedia_client.base_uri', $config['base_uri']);
        $parameters->set('api_platform.hypermedia_client.namespace', $config['namespace']);
        $parameters->set('api_platform.hypermedia_client.directory', $config['directory']);
        $parameters->set('api_platform.hypermedia_client.path_name', $config['path_name']);

        $services = $container->services();
        $services->set('api_platform.hypermedia_client.attribute_loader')
            ->class(AttributeLoader::class);

        $services->set('api_platform.hypermedia_client.class_metadata_factory')
            ->class(ClassMetadataFactory::class)->arg('$loader', service('api_platform.hypermedia_client.attribute_loader'));

        $services->set('api_platform.hypermedia_client.php_doc_extractor')
            ->class(PhpDocExtractor::class);

        $services->set('api_platform.hypermedia_client.reflection_extractor')
            ->class(ReflectionExtractor::class);

        $services->set('api_platform.hypermedia_client.property_info_extractor')
            ->class(PropertyInfoExtractor::class)->args([
                [service('api_platform.hypermedia_client.reflection_extractor')],
                [service('api_platform.hypermedia_client.php_doc_extractor'), service('api_platform.hypermedia_client.reflection_extractor')],
                [service('api_platform.hypermedia_client.php_doc_extractor')],
                [service('api_platform.hypermedia_client.reflection_extractor')],
                [service('api_platform.hypermedia_client.reflection_extractor')],
            ]);

        $services->set('api_platform.hypermedia_client.metadata_aware_name_converter')
            ->class(MetadataAwareNameConverter::class)->args([service('api_platform.hypermedia_client.class_metadata_factory')]);

        $services->set('api_platform.hypermedia_client.object_normalizer.symfony')
            ->class(ObjectNormalizer::class)
            ->arg('$classMetadataFactory', service('api_platform.hypermedia_client.class_metadata_factory'))
            ->arg('$nameConverter', service('api_platform.hypermedia_client.metadata_aware_name_converter'))
            ->arg('$propertyTypeExtractor', service('api_platform.hypermedia_client.property_info_extractor'))
            ->arg('$propertyInfoExtractor', service('api_platform.hypermedia_client.property_info_extractor'));

        $services->set('api_platform.hypermedia_client.object_normalizer')
            ->class(HydraObjectDenormalizer::class)
            ->arg('$denormalizer', service('api_platform.hypermedia_client.object_normalizer.symfony'))
            ->call('setSerializer', [service('api_platform.hypermedia_client.serializer')]);

        $services
            ->set('api_platform.hypermedia_client.http_client', HttpClientInterface::class)
            ->factory([HttpClient::class, 'create'])
            ->args([
                [
                    'base_uri' => $config['base_uri'],
                    'headers' => [
                        'Accept' => 'application/ld+json',
                    ],
                ],
            ])
            ->call('setLogger', [service('logger')->ignoreOnInvalid()])
            ->tag('monolog.logger', ['channel' => 'http_client'])
            ->tag('kernel.reset', ['method' => 'reset', 'on_invalid' => 'ignore'])
        ;

        $services->set('api_platform.hypermedia_client.array_denormalizer.symfony')
            ->class(ArrayDenormalizer::class)
            ->call('setDenormalizer', [service('api_platform.hypermedia_client.object_normalizer')]);

        $services->set('api_platform.hypermedia_client.array_denormalizer')
            ->class(HydraArrayDenormalizer::class)
            ->arg('$denormalizer', service('api_platform.hypermedia_client.array_denormalizer.symfony'));

        $services->set('api_platform.hypermedia_client.json_encoder')
            ->class(JsonEncoder::class)
            ->arg('$format', 'jsonld');

        $services->set('api_platform.hypermedia_client.serializer')
            ->class(Serializer::class)
            ->args([
                [service('api_platform.hypermedia_client.object_normalizer'), service('api_platform.hypermedia_client.array_denormalizer')],
                [service('api_platform.hypermedia_client.json_encoder')],
            ]);

        $services->set('api_platform.hypermedia_client.metadata_factory')
            ->class(HydraClassMetadataFactory::class)
            ->args([service('api_platform.hypermedia_client.http_client'), service('api_platform.hypermedia_client.serializer')]);

        $services->set('api_platform.hypermedia_client.class_metadata_dumper')
            ->class(ClassMetadataDumper::class);

        $services->set('api_platform.hypermedia_client.fetch_documentation_command')
            ->class(FetchDocumentationCommand::class)
            ->args([
                service('api_platform.hypermedia_client.metadata_factory'),
                service('api_platform.hypermedia_client.class_metadata_dumper'),
                param('api_platform.hypermedia_client.base_uri'),
                param('api_platform.hypermedia_client.namespace'),
                param('api_platform.hypermedia_client.directory'),
                param('api_platform.hypermedia_client.path_name'),
            ])->public();
    }

    public function registerCommands(Application $application): void
    {
        /** @var \Symfony\Component\Console\Command\Command */
        $command = $this->container->get('api_platform.hypermedia_client.fetch_documentation_command');
        $application->add($command);
    }
}
