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

use ApiPlatform\HypermediaClient\Exception\RuntimeException;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;

class ApiResource
{
    use PhpDocParserAwareTrait;

    /**
     * @var \ReflectionClass<$this>
     */
    private \ReflectionClass $localReflectionCache;

    /**
     * @var array<int|string, mixed>
     */
    public array $data;

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $k, mixed $v): void
    {
        $this->data[$k] = $v;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): ApiResource|Collection
    {
        $this->localReflectionCache ??= new \ReflectionClass($this);
        $attributes = $this->localReflectionCache->getAttributes(Link::class);

        foreach ($attributes as $attr) {
            $ins = $attr->newInstance();

            if ($ins->name === $name) {
                return $ins->request($this, ...$arguments);
            }
        }

        throw new RuntimeException('No link named '.$name.' found.');
    }

    /**
     * @return PropertyTagValueNode[]
     */
    public function getProperties(): array
    {
        $this->localReflectionCache ??= new \ReflectionClass($this);
        if (!$comment = $this->localReflectionCache->getDocComment()) {
            return [];
        }

        ['lexer' => $lexer, 'phpDocParser' => $phpDocParser] = $this->getPhpDocParser();

        return $phpDocParser->parse(new TokenIterator($lexer->tokenize($comment)))->getPropertyTagValues();
    }
}
