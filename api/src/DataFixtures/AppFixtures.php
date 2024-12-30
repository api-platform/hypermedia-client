<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $authors = [];
        for ($i = 0; $i < 100; $i++) {
            $author = new Author();
            $author->setGivenName($faker->name());
            $author->setFamilyName($faker->name());
            $author->setBirthDate($faker->dateTime());
            $authors[] = $author;
            $manager->persist($author);
        }
        $manager->flush();

        $faker = Factory::create();
        for ($i = 0; $i < 100; $i++) {
            $book = new Book();
            $book->setAuthor($authors[array_rand($authors)]);

            for ($j = 0; $j < random_int(1, 5); $j++) {
                $book->addAuthor($authors[array_rand($authors)]);
            }

            $manager->persist($book);
        }
        $manager->flush();
    }
}
