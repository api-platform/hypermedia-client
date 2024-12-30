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

use ArrayAccess;
use IteratorAggregate;

/**
 * @template T
 *
 * @implements ArrayAccess<int,T>
 * @implements IteratorAggregate<int,T>
 */
class Collection extends ApiResource implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array{member: T[]}&array<mixed, mixed>
     */
    public array $data = ['member' => []];

    public function offsetExists($offset): bool
    {
        return isset($this->data['member'][$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data['member'][$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->data['member'][$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data['member'][$offset]);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data['member']);
    }
}
