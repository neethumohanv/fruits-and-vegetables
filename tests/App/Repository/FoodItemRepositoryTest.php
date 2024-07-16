<?php
namespace App\Tests\App\Repository;

use App\Entity\FoodItem;
use App\Repository\FoodItemRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class FoodItemRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FoodItemRepository $foodItemRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->foodItemRepository = $this->entityManager->getRepository(FoodItem::class);

        // Optional: Clean up the database before each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\FoodItem')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear the EntityManager to avoid memory leaks
        $this->entityManager->clear();
    }

    public function testSaveAndFindById(): void
    {
        $foodItem = new FoodItem();
        $foodItem->setName('Banana');
        $foodItem->setType('fruit');
        $foodItem->setQuantity(1000, 'kg');

        $this->foodItemRepository->save($foodItem);

        $savedFoodItem = $this->foodItemRepository->findById($foodItem->getId());

        $this->assertNotNull($savedFoodItem);
        $this->assertEquals('Banana', $savedFoodItem->getName());
        $this->assertEquals('fruit', $savedFoodItem->getType());
        $this->assertEquals(1000000, $savedFoodItem->getQuantity()); // 1000 kg to grams
    }

    public function testFindAllFoodItems(): void
    {
        $foodItem1 = new FoodItem();
        $foodItem1->setName('Banana');
        $foodItem1->setType('fruit');
        $foodItem1->setQuantity(1000, 'kg');

        $foodItem2 = new FoodItem();
        $foodItem2->setName('Carrot');
        $foodItem2->setType('vegetable');
        $foodItem2->setQuantity(500, 'kg');

        $this->foodItemRepository->save($foodItem1);
        $this->foodItemRepository->save($foodItem2);

        $allFoodItems = $this->foodItemRepository->findAllFoodItems();

        $this->assertCount(2, $allFoodItems);
        $this->assertEquals(1000000, $allFoodItems[0]->getQuantity()); // 1000 kg to grams
        $this->assertEquals(500000, $allFoodItems[1]->getQuantity()); // 500 kg to grams
    }

    public function testFindFruits(): void
    {
        $fruit = new FoodItem();
        $fruit->setName('Apple');
        $fruit->setType('fruit');
        $fruit->setQuantity(2000, 'g');

        $vegetable = new FoodItem();
        $vegetable->setName('Broccoli');
        $vegetable->setType('vegetable');
        $vegetable->setQuantity(100, 'kg');

        $this->foodItemRepository->save($fruit);
        $this->foodItemRepository->save($vegetable);

        $fruits = $this->foodItemRepository->findFruits();

        $this->assertCount(1, $fruits);
        $this->assertEquals('Apple', $fruits[0]->getName());
        $this->assertEquals(2000, $fruits[0]->getQuantity());
    }

    public function testFindVegetables(): void
    {
        $fruit = new FoodItem();
        $fruit->setName('Apple');
        $fruit->setType('fruit');
        $fruit->setQuantity(200, 'kg');

        $vegetable = new FoodItem();
        $vegetable->setName('Broccoli');
        $vegetable->setType('vegetable');
        $vegetable->setQuantity(100, 'kg');

        $this->foodItemRepository->save($fruit);
        $this->foodItemRepository->save($vegetable);

        $vegetables = $this->foodItemRepository->findVegetables();

        $this->assertCount(1, $vegetables);
        $this->assertEquals('Broccoli', $vegetables[0]->getName());
        $this->assertEquals(100000, $vegetables[0]->getQuantity()); // 100 kg to grams
    }

    public function testRemoveFoodItem(): void
    {
        $foodItem = new FoodItem();
        $foodItem->setName('Strawberry');
        $foodItem->setType('fruit');
        $foodItem->setQuantity(300, 'kg');
        $this->foodItemRepository->save($foodItem);
        $foodItemId = $foodItem->getId();
        $this->assertNotNull($foodItemId);
        $this->foodItemRepository->remove($foodItem);
        $deletedFoodItem = $this->foodItemRepository->findById($foodItemId );       
        $this->assertNull($deletedFoodItem);
    }

    public function testFindSearchByCriteriaWithName()
    {
        $foodItem = new FoodItem();
        $foodItem->setName('Apple')->setType('fruit')->setQuantity(1000);
        $this->entityManager->persist($foodItem);
        $this->entityManager->flush();

        $criteria = ['name' => 'Apple'];
        $type = 'fruit';

        $result = $this->foodItemRepository->findSearchByCriteria($criteria, $type);

        $this->assertCount(1, $result);
        $this->assertSame('Apple', $result[0]->getName());
        $this->assertSame('fruit', $result[0]->getType());
    }

    public function testFindSearchByCriteriaWithQuantity()
    {        
        $foodItem = new FoodItem();
        $foodItem->setName('Banana')->setType('fruit')->setQuantity(1000);
        $this->entityManager->persist($foodItem);
        $this->entityManager->flush();

        $criteria = ['quantity' => '>500'];
        $type = 'fruit';

        $result = $this->foodItemRepository->findSearchByCriteria($criteria, $type);

        $this->assertCount(1, $result);
        $this->assertSame('Banana', $result[0]->getName());
        $this->assertSame('fruit', $result[0]->getType());
    }

    public function testFindSearchByCriteriaWithInvalidQuantityFilter()
    {
        $this->expectException(\InvalidArgumentException::class);

        $criteria = ['quantity' => 'invalid'];
        $type = 'fruit';

        $this->foodItemRepository->findSearchByCriteria($criteria, $type);
    }
}
