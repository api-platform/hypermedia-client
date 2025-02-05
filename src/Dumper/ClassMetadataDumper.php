<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Dumper;

use ApiPlatform\HypermediaClient\ApiResource;
use ApiPlatform\HypermediaClient\Exception\RuntimeException;
use ApiPlatform\HypermediaClient\Metadata\ClassMetadata;

final class ClassMetadataDumper
{
    public function dump(ClassMetadata $classMetadata): string
    {
        $t = "
namespace {$classMetadata->namespace}; 

/**
 * @id {$classMetadata->id}".PHP_EOL;

        foreach ($classMetadata->properties as $name => $p) {
            $t .= ' * @property '.$p->type.' $'.$name.PHP_EOL;
        }

        foreach ($classMetadata->methods as $key => $p) {
            $input = $p->input;
            $t .= ' * @method '.$p->output.' '.$key.'('.($input ? "$input \$options = null" : '').') '.($p->description ?? '').PHP_EOL;
        }

        $t .= ' */'.PHP_EOL;
        foreach ($classMetadata->attributes as $attribute) {
            $t .= "#[{$attribute->className}(".PHP_EOL;
            $args = [];

            foreach ($attribute->arguments as $arg => $value) {
                if (is_array($value)) {
                    $value = $this->toArrayString($value);
                } elseif (is_string($value)) {
                    $value = $this->toString($value);
                }

                $args[] = "    {$arg}: $value";
            }

            $t .= implode(', '.PHP_EOL, $args);

            $t .= PHP_EOL.')]'.PHP_EOL;
        }

        $t .= "class {$classMetadata->name} extends \\".ApiResource::class;

        $t .= PHP_EOL.'{'.PHP_EOL.'}';

        return $t;
    }

    private function mixedToString(mixed $v): string
    {
        if (is_array($v)) {
            return $this->toArrayString($v);
        }

        if (is_string($v)) {
            return $this->toString($v);
        }

        throw new RuntimeException('Type not supported.');
    }

    private function toString(string $v): string
    {
        return "'$v'";
    }

    /**
     * @param array<string, mixed> $v
     */
    private function toArrayString(array $v): string
    {
        $r = '[';
        foreach ($v as $key => $value) {
            $r .= $this->toString($key).' => '.$this->mixedToString($value);
        }

        return $r.']';
    }
}
