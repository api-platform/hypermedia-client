<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Antoine Bluchet <soyuka@pm.me>, Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\HypermediaClient\Tests\Functional;

use ApiPlatform\HypermediaClient\Collection;
use ApiPlatform\HypermediaClient\Exception\HttpClientException;
use ApiPlatform\HypermediaClient\Link;
use MyApp\ApiResource\Author;
use MyApp\ApiResource\Book;
use MyApp\ApiResource\Entrypoint;
use MyApp\Kernel;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversNothing]
class EndToEndTest extends TestCase
{
    private static Kernel $kernel;

    public function setUp(): void
    {
        self::$kernel = new Kernel('prod', false);
        $application = new Application(self::$kernel);
        $command = $application->get('hydra:fetch-docs');
        $command->run(new ArrayInput(['--force' => true]), new BufferedOutput());
    }

    public function tearDown(): void
    {
        self::$kernel->shutdown();
    }

    public function testCrud(): void
    {
        $author = new Author();
        $collection = $author->getAuthorCollection();
        $this->assertCount(30, $collection);
        $this->assertInstanceOf(Collection::class, $collection);
        $collection = $author->getAuthorCollection(['query' => ['itemsPerPage' => 10]]);
        $this->assertCount(10, $collection);

        $first = $collection[0];
        // create a clone to refresh it later
        $clone = clone $first;
        $this->assertInstanceOf(Author::class, $first);

        $name = bin2hex(random_bytes(10));
        $first->familyName = $name;
        $first->patchAuthor();

        $this->assertEquals($first->familyName, $name);
        $this->assertNotEquals($first->familyName, $clone->familyName);

        // refresh the memory author to update its name
        $clone = $clone->getAuthor();
        $this->assertEquals($clone->familyName, $name);

        $anotherOne = new Author();
        $anotherOne->givenName = 'Test';
        $anotherOne->familyName = 'Test2';
        $anotherOne->birthDate = new \DateTimeImmutable();
        $anotherOne = $anotherOne->postAuthor();

        $this->assertInstanceOf(Link::class, $anotherOne->{'@id'}); // @phpstan-ignore-line impossible to document @id property name
    }

    public function testEntrypoint(): void
    {
        $entrypoint = (new Entrypoint())->index();
        $author = $entrypoint->author;
        $collection = $author->request(null);
        $this->assertInstanceOf(Collection::class, $collection);
        $books = $entrypoint->book;
        $collection = $books->request(null);
        $this->assertInstanceOf(Book::class, $collection[0]);
    }

    public function testRelationToMany(): void
    {
        $book = new Book();
        $collection = $book->getBookCollection();
        foreach ($collection[0]->authors as $author) {
            $this->assertInstanceOf(Link::class, $author);
        }
    }

    public function testConstraintViolation(): void
    {
        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('givenName: This value should not be blank.');
        $anotherOne = new Author();
        $anotherOne->givenName = '';
        $anotherOne->familyName = 'Test2';
        $anotherOne->birthDate = new \DateTimeImmutable();
        $anotherOne = $anotherOne->postAuthor();
    }

    public function testWithKnownIdentity(): void
    {
        $a = new Author();
        $a->givenName = 'Test';
        $a->familyName = 'Test2';
        $a->birthDate = new \DateTimeImmutable();
        $a = $a->postAuthor();

        $anotherOne = new Author();
        $anotherOne->{'@id'} = (string) $a->{'@id'}; // @phpstan-ignore-line
        $this->assertEquals($a, $anotherOne->getAuthor());
    }
}
