<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'api',
        'var',
    ])
;

$header = <<<'HEADER'
This file is part of the API Platform project.

(c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com> 

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PHP82Migration' => true,
        '@Symfony' => true,
        'fully_qualified_strict_types' => true,
        'header_comment' => [
            'header' => $header,
            'location' => 'after_open',
        ],
    ])
    ->setFinder($finder)
;
