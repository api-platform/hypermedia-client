# Hypermedia Client

Creates an HTTP Hypermedia client that leverages the [Hydra specification](https://www.hydra-cg.com/) 
that makes Web APIs interoperable and set-up PHP classes for each documented API Resource. 

## Installation

```console
composer global require api-platform/hypermedia-client
```

Or [download the latest release](https://github.com/api-platform/hypermedia-client/releases).

## Usage

```console
fetch-docs https://pr-500-demo.api-platform.com/docs -vvv --namespace '\App' --directory demo/
```

Fetch the documentation and create resource classes inside the `demo` directory.

```php
<?php

use \App\Book;

$books = (new Book)->getBookCollection();

$book = $books[0];
$book->title = '1984';
$book->patchBook();
```

## Internals

Each resource is created as a PHP class with documented properties and HTTP operations:

```php
# demo/ApiResource/Book.php
<?php

namespace App; 

/**
 * @id https://schema.org/Book
 * @property string $book
 * @property string $title
 * @property string $author
 * @property mixed $condition
 * @property integer $rating
 * @method \App\Book getBookCollection(?array<string, mixed> $options = null)
 */
#[\ApiPlatform\HypermediaClient\Link(
    name: 'getBookCollection', 
    baseUri: 'https://demo.api-platform.com', 
    method: 'GET', 
    output: '\App\Book', 
    uriTemplatePropertyPath: '@id'
)]
class Book extends \ApiPlatform\HypermediaClient\ApiResource
{
}
```
