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

use ApiPlatform\HypermediaClient\Exception\HttpClientException;
use ApiPlatform\HypermediaClient\Exception\RuntimeException;
use ApiPlatform\HypermediaClient\Serializer\ApiResourceNormalizer;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @template T of ApiResource
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Link implements \Stringable, \JsonSerializable
{
    use PhpDocParserAwareTrait;

    private static SerializerInterface $serializer;
    private static HttpClientInterface $httpClient;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        public string $method = 'GET',
        public ?string $uriTemplate = null,
        public ?string $name = null,
        public ?string $baseUri = null,
        public string|TypeNode|null $output = null,
        public ?string $uriTemplatePropertyPath = null,
        public ?array $headers = null,
        public string|TypeNode|null $input = null,
    ) {
    }

    /**
     * @param T|null                                                                                                                                   $callee
     * @param array{client?: HttpClientInterface, serializer?: SerializerInterface, headers?: array<string, mixed>, json?: mixed}&array<string, mixed> $options HTTPClient request options
     *
     * @return T|Collection<T>
     */
    public function request(?object $callee, array $options = []): mixed
    {
        $client = $options['client'] ?? self::getHttpClient($this->baseUri);
        unset($options['client']);
        if (!$this->uriTemplate) {
            if (!$propertyPath = $this->uriTemplatePropertyPath) {
                throw new RuntimeException('No URI.');
            }

            $uriTemplate = $callee->{$propertyPath};
            $this->uriTemplate = $uriTemplate instanceof Link ? $uriTemplate->uriTemplate : $uriTemplate;

            if (!$this->uriTemplate) {
                throw new RuntimeException('No URI.');
            }
        }

        $headers = $this->headers ?? ['accept' => 'application/ld+json'];
        $body = null;
        if ($this->input) {
            if (!isset($headers['content-type'])) {
                $headers['content-type'] = 'application/ld+json';
            }

            if (is_a($callee, $this->input, true)) {
                $body = $callee;

                if (interface_exists(SerializerInterface::class) && ($serializer = $options['serializer'] ?? self::getSerializer()) && $serializer instanceof NormalizerInterface) { // @phpstan-ignore-line
                    $body = $serializer->normalize($callee, 'json');
                }
            }
        }
        unset($options['serializer']);

        $opts = ['headers' => ($options['headers'] ?? []) + $headers] + $options;
        if ($body && !isset($opts['json'])) {
            $opts['json'] = $body;
        }

        $res = $client->request($this->method, $this->uriTemplate, $opts);
        $arr = $res->toArray(false);

        if (($code = $res->getStatusCode()) >= 400) {
            $type = $arr['@type'] ?? 'Error';
            $refl = new \ReflectionClass($callee::class);
            $className = $refl->getNamespaceName().'\\'.$type;
            if (!class_exists($className) || !is_a($className, ApiResource::class, true)) {
                $this->checkStatusCode($code, $res);
            }

            $data = $this->denormalize($arr, $this, new $className()); // @phpstan-ignore-line we throw therefore this doesn't matter
            throw new HttpClientException($data, $data->detail ?? $data->description ?? $data->title ?? '', $code);
        }

        $output = $this->output = $this->parseType($this->output);

        if ($output instanceof GenericTypeNode && is_a($output->type->name, Collection::class, true)) {
            return $this->denormalizeCollection($arr, $this, new Collection());
        }

        if ($output instanceof IdentifierTypeNode) {
            $output = $output->name;
        }

        if (is_string($output) && class_exists($output)) {
            /** @var class-string<T> $output */
            $apiResource = new $output();
        } else {
            $apiResource = clone $callee;
        }

        if (!$apiResource instanceof ApiResource) {
            throw new RuntimeException('Resource class not found.');
        }

        return $this->denormalize($arr, $this, $apiResource);
    }

    /**
     * @param array<string, mixed> $data
     * @param Link<T>              $link
     * @param T                    $apiResource
     *
     * @return T
     */
    public function denormalize(array $data, Link $link, ApiResource $apiResource): ApiResource
    {
        foreach ($apiResource->getProperties() as $property) {
            $propertyName = str_replace('$', '', $property->propertyName);

            if ($property->type instanceof ArrayTypeNode && $property->type->type instanceof GenericTypeNode && is_a($property->type->type->type->name, Link::class, true)) {
                $property->type = $property->type->type;
            }

            if ($property->type instanceof GenericTypeNode && is_a($property->type->type->name, Link::class, true)) {
                $uriTemplate = $data[$propertyName] ?? null;

                if (is_array($uriTemplate)) {
                    $apiResource->data[$propertyName] = array_map(function ($v) use ($link, $propertyName, $property) {
                        return new Link(
                            baseUri: $link->baseUri,
                            uriTemplate: $v,
                            name: $propertyName,
                            output: $property->type->genericTypes[0]
                        );
                    }, $uriTemplate);

                    continue;
                }

                $apiResource->data[$propertyName] = new Link(
                    baseUri: $link->baseUri,
                    uriTemplate: $uriTemplate,
                    name: $propertyName,
                    output: $property->type->genericTypes[0]
                );

                continue;
            }

            if (isset($data[$propertyName])) {
                $apiResource->data[$propertyName] = $data[$propertyName];
            }

            if (isset($data['@id'])) {
                $apiResource->data['@id'] = new Link(
                    baseUri: $link->baseUri,
                    uriTemplate: $data['@id'],
                    name: 'self',
                    output: $apiResource::class
                );
            }
        }

        return $apiResource;
    }

    /**
     * TODO: type array as partial view collection ?
     *
     * @param array<string|int, mixed> $data
     * @param Link<T>                  $link
     * @param Collection<T>            $apiResource
     *
     * @return Collection<T>
     */
    public function denormalizeCollection(array $data, Link $link, Collection $apiResource): Collection
    {
        $apiResource->data['member'] = [];
        if ($link->output instanceof GenericTypeNode && isset($link->output->genericTypes[0]) && $link->output->genericTypes[0] instanceof IdentifierTypeNode) {
            /** @var class-string<T> */
            $collectionType = $link->output->genericTypes[0]->name;

            if (!is_a($collectionType, ApiResource::class, true)) {
                throw new RuntimeException(sprintf('Class "%s" is not a "%s".', $collectionType, ApiResource::class));
            }
        } else {
            throw new RuntimeException(sprintf('Could nout found the collection type for link "%s".', $link->name));
        }

        foreach ($data['member'] ?? $data['hydra:member'] ?? [] as $v) {
            $member = new $collectionType();
            $apiResource->data['member'][] = $this->denormalize($v, $link, $member);
        }

        return $apiResource;
    }

    private function parseType(string $stringType): TypeNode
    {
        ['lexer' => $lexer, 'typeParser' => $typeParser] = $this->getPhpDocParser();
        $tokens = new TokenIterator($lexer->tokenize($stringType));

        return $typeParser->parse($tokens);
    }

    private function getSerializer(): SerializerInterface
    {
        if (!isset(self::$serializer)) {
            self::$serializer = new Serializer([new DateIntervalNormalizer(), new DateTimeZoneNormalizer(), new DateTimeNormalizer(), new JsonSerializableNormalizer(), new UidNormalizer(), new ApiResourceNormalizer()]);
        }

        return self::$serializer;
    }

    private function getHttpClient(string $baseUri): HttpClientInterface
    {
        if (!isset(self::$serializer)) {
            self::$httpClient = HttpClient::createForBaseUri($baseUri);
        }

        return self::$httpClient;
    }

    public function __toString(): string
    {
        return $this->uriTemplate;
    }

    public function jsonSerialize(): mixed
    {
        return $this->uriTemplate;
    }

    private function checkStatusCode(int $code, ResponseInterface $response): void
    {
        if (500 <= $code) {
            throw new ServerException($response);
        }

        if (400 <= $code) {
            throw new ClientException($response);
        }

        if (300 <= $code) {
            throw new RedirectionException($response);
        }
    }
}
