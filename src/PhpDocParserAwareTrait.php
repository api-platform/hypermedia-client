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

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;

trait PhpDocParserAwareTrait
{
    /**
     * @var array{lexer: Lexer, phpDocParser: PhpDocParser, typeParser: TypeParser}|array{}
     */
    private static $dependencies = [];

    /**
     * @return array{lexer: Lexer, phpDocParser: PhpDocParser, typeParser: TypeParser}
     */
    private function getPhpDocParser(): array
    {
        if (isset(self::$dependencies['lexer'])) {
            return self::$dependencies;
        }

        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);

        return self::$dependencies = [
            'lexer' => new Lexer(),
            'typeParser' => $typeParser,
            'phpDocParser' => new PhpDocParser($typeParser, $constExprParser),
        ];
    }
}
