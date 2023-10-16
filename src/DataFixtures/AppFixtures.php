<?php

namespace App\DataFixtures;

use App\Entity\Provider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->setProviders($manager);
        $manager->flush();
    }

    private function setProviders(ObjectManager $manager): void
    {
        $paypalProvider = (new Provider)->setInternalKey('paypal')->setName("Paypal provider");
        $manager->persist($paypalProvider);
    }
}
