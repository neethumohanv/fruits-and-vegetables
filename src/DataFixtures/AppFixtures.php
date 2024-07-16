<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\FoodItem;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $foodItem = new FoodItem();
        $foodItem->setName('Test Fruit');
        $foodItem->setType('fruit');
        $foodItem->setQuantity(100);

        $manager->persist($foodItem);
        $manager->flush();
    }
}
