<?php

namespace App\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class FoodItemTest extends KernelTestCase
{
     /** @var EntityManagerInterface */
     protected $entityManager;

     protected function setUp(): void
     {
         $kernel = self::bootKernel();
 
         DatabasePrimer::prime($kernel);
 
         $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
     }
    //  protected function tearDown(): void
    // {
    //     parent::tearDown();

    //     $this->entityManager->close();
    //     $this->entityManager = null;
    // }

    public function testItWorks(){
        $this->assertTrue(true);
    }
}