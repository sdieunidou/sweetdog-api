<?php

declare(strict_types=1);

namespace Infrastructure\Auth\Symfony\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Infrastructure\Auth\Doctrine\Entity\User;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = User::create('cac340b0-9682-43ac-9de5-c2a3ae1cdef0');

        $manager->persist($user);
        $manager->flush();
    }
}
