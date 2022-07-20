<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $course = new Course();
        $course->setCode('uid1');
        $course->setTitle('Python Basic');
        $course->setType(0);
        $manager->persist($course);
        $course = new Course();
        $course->setCode('uid2');
        $course->setTitle('Java-разработчик');
        $course->setType(1);
        $course->setPrice(15.0);
        $manager->persist($course);
        $course->setCode('uid3');
        $course->setTitle('1С-разработчик');
        $course->setType(2);
        $course->setPrice(1500.0);
        $manager->persist($course);
        $manager->flush();
    }
}
