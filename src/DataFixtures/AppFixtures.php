<?php

namespace App\DataFixtures;

use App\Entity\Method;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->loadMethods($manager);
        $manager->flush();
    }

    private function loadMethods(ObjectManager $manager): void
    {
        $paypalMethod = (new Method)->setInternalKey('paypal')->setName("Paypal provider");
        $manager->persist($paypalMethod);
    }
}
