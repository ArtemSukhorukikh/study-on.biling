<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('ArtemSukhorukikh@yandex.ru');
        $user->setPassword($this->passwordHasher->hashPassword($user,'123qwe'));
        $manager->persist($user);
        $userAdmin = new User();
        $userAdmin->setEmail('Admin@mail.ru');
        $userAdmin->setPassword($this->passwordHasher->hashPassword($user,'123qwe'));
        $userAdmin->setRoles(['ROLE_SUPER_ADMIN']);
        $manager->persist($userAdmin);
        $manager->flush();
    }
}
