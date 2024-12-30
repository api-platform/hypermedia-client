<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Command;

use ApiPlatform\HypermediaClient\Dumper\ClassMetadataDumper;
use ApiPlatform\HypermediaClient\Metadata\Factory\ClassMetadataFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

final class FetchDocumentationCommand extends Command
{
    public function __construct(
        private readonly ClassMetadataFactory $hydraClassMetadataFactory,
        private readonly ClassMetadataDumper $classMetadataDumper,
        private readonly string $baseUri,
        private readonly string $namespace,
        private readonly string $directory,
        private readonly string $pathName,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parsed = $pathName = null;
        $baseUri = $input->getArgument('uri');
        $force = $input->getOption('force');

        if ($baseUri && !($parsed = parse_url($baseUri))) {
            throw new \RuntimeException('Malformed URI.');
        }

        if ($parsed && $parsed['path'] && '/' !== $parsed['path']) {
            $pathName = $parsed['path'];
            $pos = strpos($baseUri, $pathName);
            if ($pos) {
                $baseUri = substr($baseUri, 0, $pos);
            }
        }

        $context = [
            'baseUri' => $baseUri ?? $this->baseUri,
            'namespace' => $input->getOption('namespace') ?? $this->namespace,
            'directory' => $input->getOption('directory') ?? $this->directory,
            'pathName' => $pathName ?? $this->pathName,
            'httpClient' => HttpClient::create(
                [
                    'base_uri' => $baseUri ?? $this->baseUri,
                    'headers' => [
                        'Accept' => 'application/ld+json',
                    ],
                ],
            ),
        ];

        foreach ($this->hydraClassMetadataFactory->create($context) as $classMetadata) {
            $directory = dirname($classMetadata->filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0o755, true);
            }

            if (file_exists($classMetadata->filePath) && !$force) {
                continue;
            }

            file_put_contents($classMetadata->filePath, '<?php'.PHP_EOL.$this->classMetadataDumper->dump($classMetadata));
        }

        return Command::SUCCESS;
    }

    public function getName(): string
    {
        return 'hydra:fetch-docs';
    }

    protected function configure(): void
    {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Fetch an hydra documentation and create API Resource classes in the specify directory.')
            ->addArgument('uri', mode: InputArgument::OPTIONAL, default: null, description: 'The base URI to fetch the Hydra documentation from.')
            ->addOption('namespace', mode: InputOption::VALUE_REQUIRED, description: 'The PSR-4 namespace prefix for generated classes. Defaults to '.$this->namespace)
            ->addOption('directory', mode: InputOption::VALUE_REQUIRED, description: 'The directory to dump the classes to. Defaults to '.$this->directory)
            ->addOption('force', shortcut: 'f', description: 'Overwrite files.')
        ;
    }
}
