<?php
namespace App\Tests\App\Service;

use App\Entity\FoodItem;
use App\Repository\FoodItemRepository;
use App\Service\FoodItemService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use App\Exception\ValidationException;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;
use InvalidArgumentException;


class FoodItemServiceTest extends TestCase
{
    private $entityManager;
    private $foodItemRepository;
    private $validator;
    private $foodItemService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->foodItemRepository = $this->createMock(FoodItemRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->foodItemService = new FoodItemService($this->entityManager, $this->foodItemRepository,  $this->validator);
    }

     public function testAddFoodItemValidData(): void
    {
        $data = [
            'name' => 'Banana',
            'type' => 'fruit',
            'quantity' => 1000,
            'unit' => 'kg'
        ];

        $this->foodItemRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(FoodItem::class));

        $foodItem = $this->foodItemService->addFoodItem($data);

        $this->assertInstanceOf(FoodItem::class, $foodItem);
        $this->assertEquals('Banana', $foodItem->getName());
        $this->assertEquals('fruit', $foodItem->getType());
        $this->assertEquals(1000000, $foodItem->getQuantity()); // converted to grams
    }

    public function testAddFoodItemWithInvalidData()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => '',
            'type' => 'fruit',
            'quantity' => -5,
            'unit' => 'kg'
        ];

        $violation = new ConstraintViolation(
            'This value should not be blank.', // error message
            null,           // message template
            [],             // message parameters
            '',             // root
            '',             // property path
            '',             // invalid value
            null,           // plural
            null            // code
        );
        $violations = new ConstraintViolationList([$violation]);

        $this->validator->expects($this->any())->method('validate')->willReturn($violations);
        $this->foodItemService->addFoodItem($data);
    }

    public function testListFoodItems()
    {
        $this->foodItemRepository->expects($this->once())->method('findAllFoodItems')->willReturn([]);
        $result = $this->foodItemService->listFoodItems();
        $this->assertIsArray($result);
    }

    public function testListFoodItemsByType()
    {
        $this->foodItemRepository->expects($this->once())->method('findFruits')->willReturn([]);
        $this->foodItemRepository->expects($this->once())->method('findVegetables')->willReturn([]);

        $result = $this->foodItemService->listFoodItemsByType('grams');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fruits', $result);
        $this->assertArrayHasKey('vegetables', $result);
    }

    public function testListFoodItemsByTypeAndUnitConversion(): void
    {
        // Create dummy FoodItem objects
        $fruit = new FoodItem();
        $fruit->setName('Apple')->setType('fruit')->setQuantity(1000); // 1000 grams

        $vegetable = new FoodItem();
        $vegetable->setName('Carrot')->setType('vegetable')->setQuantity(2000); // 2000 grams

        // Mock repository methods
        $this->foodItemRepository->expects($this->once())
            ->method('findFruits')
            ->willReturn([$fruit]);

        $this->foodItemRepository->expects($this->once())
            ->method('findVegetables')
            ->willReturn([$vegetable]);

        // Call the method and assert the expected result
        $result = $this->foodItemService->listFoodItemsByType('kg');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fruits', $result);
        $this->assertArrayHasKey('vegetables', $result);
        $this->assertCount(1, $result['fruits']);
        $this->assertCount(1, $result['vegetables']);
        $this->assertSame($fruit, $result['fruits'][0]);
        $this->assertSame($vegetable, $result['vegetables'][0]);

        // Verify the quantities are converted (assuming 1 kg = 1000 grams)
        $this->assertEquals(1, $result['fruits'][0]->getQuantity()); // 1000 grams to 1 kg
        $this->assertEquals(2, $result['vegetables'][0]->getQuantity()); // 2000 grams to 2 kg
    }

    // public function testAddFoodItemInvalidType(): void
    // {
    //     $this->expectException(ValidationException::class);
    //     $data = [
    //         'name' => 'Banana',
    //         'type' => 'meat',
    //         'quantity' => 1000,
    //         'unit' => 'kg'
    //     ];

    //     // Mock validator to return a violation
    //     $constraintViolation = new ConstraintViolation(
    //         'The value you selected is not a valid choice.',
    //         '',
    //         [],
    //         '',
    //         'type',
    //         'unknown'
    //     );

    //     $this->validator->expects($this->once())
    //         ->method('validate')
    //         ->willReturn(new ConstraintViolationList([$constraintViolation]));

    //     $this->expectException(ExceptionInvalidArgumentException::class);
    //     $this->expectExceptionMessage('Invalid type. Allowed types are "fruit" and "vegetable".');

    //     $this->foodItemService->addFoodItem($data);

    //     // $this->expectException(\InvalidArgumentException::class);
    //     // $this->expectExceptionMessage('Invalid type. Allowed types are "fruit" and "vegetable".');

    //     // $this->foodItemService->addFoodItem($data);
    // }

    // public function testAddFoodItemNonNumericQuantity(): void
    // {
    //     $data = [
    //         'name' => 'Banana',
    //         'type' => 'fruit',
    //         'quantity' => 'one thousand',
    //         'unit' => 'kg'
    //     ];

    //     $this->expectException(\InvalidArgumentException::class);
    //     $this->expectExceptionMessage('Quantity must be a positive number.');

    //     $this->foodItemService->addFoodItem($data);
    // }


   

    public function testProcessRequestJsonValidData(): void
    {
        $data = [
            ['name' => 'Banana', 'type' => 'fruit', 'quantity' => 1000, 'unit' => 'kg'],
            ['name' => 'Carrot', 'type' => 'vegetable', 'quantity' => 2000, 'unit' => 'kg']
        ];

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(FoodItem::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->foodItemService->processRequestJson($data, $this->validator);

        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(1, $result['processed']['fruits']);
        $this->assertCount(1, $result['processed']['vegetables']);
        $this->assertCount(0, $result['errors']);
        $this->assertTrue($result['successful']);
    }

    public function testProcessRequestJsonPartialValidData(): void
    {
        $data = [
            ['name' => 'Banana', 'type' => 'fruit', 'quantity' => 1000, 'unit' => 'kg'],
            ['name' => 'Carrot', 'type' => 'meat', 'quantity' => 2000, 'unit' => 'kg']
        ];

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList(),
                new ConstraintViolationList([new ConstraintViolation('The value you selected is not a valid choice.', '', [], '', '', '')])
            );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(FoodItem::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->foodItemService->processRequestJson($data, $this->validator);

        $this->assertArrayHasKey('processed', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertCount(1, $result['processed']['fruits']);
        $this->assertCount(0, $result['processed']['vegetables']);
        $this->assertCount(1, $result['errors']);
        $this->assertTrue($result['successful']);
    }

    public function testDeleteFoodItemByIdWithValidId()
    {
        $foodItem = new FoodItem();
        $this->foodItemRepository->expects($this->once())->method('findById')->willReturn($foodItem);
        $this->foodItemRepository->expects($this->once())->method('remove');

        $result = $this->foodItemService->deleteFoodItemById(1);
        $this->assertTrue($result);
    }

    public function testDeleteFoodItemByIdWithInvalidId()
    {
        $this->foodItemRepository->expects($this->once())->method('findById')->willReturn(null);

        $result = $this->foodItemService->deleteFoodItemById(999);
        $this->assertFalse($result);
    }

    public function testSearchFoodItemsByTypeWithValidType()
    {
        $criteria = ['name' => 'Apple'];
        $this->foodItemRepository->expects($this->once())->method('findSearchByCriteria')->with($criteria, 'fruit')->willReturn([]);

        $result = $this->foodItemService->searchFoodItemsByType('fruit', $criteria);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fruit', $result);
    }

    
    public function testSearchFoodItemsByTypeWithInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $criteria = ['name' => 'Apple'];
        $this->foodItemService->searchFoodItemsByType('meat', $criteria);
    }

    public function testSearchFoodItemsByTypeWithoutType1()
    {
        $criteria = ['name' => 'Apple'];
        $this->foodItemRepository->expects($this->exactly(2))
            ->method('findSearchByCriteria')
            ->withConsecutive(
                [$criteria, 'fruit'],
                [$criteria, 'vegetable']
            )
            ->willReturnOnConsecutiveCalls([], []);

        $result = $this->foodItemService->searchFoodItemsByType(null, $criteria);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fruits', $result);
        $this->assertArrayHasKey('vegetables', $result);
    }

    public function testSearchFoodItemsByTypeWithoutType()
    {
        $criteria = ['name' => 'Apple'];

        // Create mock FoodItem objects
        $fruitItem = new FoodItem();
        $fruitItem->setName('Apple')->setType('fruit')->setQuantity(1000); // 1000 grams

        // $vegetableItem = new FoodItem();
        // $vegetableItem->setName('Carrot')->setType('vegetable')->setQuantity(2000); // 2000 grams

        // Set expectations for the findSearchByCriteria method
        $this->foodItemRepository->expects($this->exactly(2))
            ->method('findSearchByCriteria')
            ->withConsecutive(
                [$criteria, 'fruit'],
                [$criteria, 'vegetable']
            )
            ->willReturnOnConsecutiveCalls([$fruitItem], []);

        // Call the service method
        $result = $this->foodItemService->searchFoodItemsByType(null, $criteria);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fruits', $result);
        $this->assertArrayHasKey('vegetables', $result);
        $this->assertCount(1, $result['fruits']);
        $this->assertCount(0, $result['vegetables']);
        $this->assertSame($fruitItem, $result['fruits'][0]);
    }

    public function testSearchFoodItemsByTypeFruit()
    {
        $criteria = ['name' => 'Apple'];

        // Create mock FoodItem objects with the name 'Apple'
        $fruitItem = new FoodItem();
        $fruitItem->setName('Apple')->setType('fruit')->setQuantity(1000); // 1000 grams

        // Set expectations for the findSearchByCriteria method
        $this->foodItemRepository->expects($this->once())
            ->method('findSearchByCriteria')
            ->with($criteria, 'fruit')
            ->willReturn([$fruitItem]);

        // Call the service method
        $result = $this->foodItemService->searchFoodItemsByType('fruit', $criteria);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('fruit', $result);
        $this->assertCount(1, $result['fruit']);
        $this->assertSame($fruitItem, $result['fruit'][0]);
    }

    public function testSearchFoodItemsByTypeVegetable()
    {
        $criteria = ['name' => 'Carrot'];

        // Create mock FoodItem objects with the name 'Carrot'
        $vegetableItem = new FoodItem();
        $vegetableItem->setName('Carrot')->setType('vegetable')->setQuantity(1000);

        // Set expectations for the findSearchByCriteria method
        $this->foodItemRepository->expects($this->once())
            ->method('findSearchByCriteria')
            ->with($criteria, 'vegetable')
            ->willReturn([$vegetableItem]);

        // Call the service method
        $result = $this->foodItemService->searchFoodItemsByType('vegetable', $criteria);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('vegetable', $result);
        $this->assertCount(1, $result['vegetable']);
        $this->assertSame($vegetableItem, $result['vegetable'][0]);
    }
    // public function testProcessRequestJsonWithMixedValidity()
    // {
    //     $data = [
    //         [
    //             'name' => 'Grapes',
    //             'type' => 'fruit',
    //             'quantity' => 1,
    //             'unit' => 'kg'
    //         ],
    //         [
    //             'name' => 'Broccoli',
    //             'type' => 'vegetable',
    //             'quantity' => -1,
    //             'unit' => 'kg'
    //         ]
    //     ];

    //     $this->validator->expects($this->any())
    //         ->method('validate')
    //         ->willReturnOnConsecutiveCalls(
    //             new ConstraintViolationList(),
    //             new ConstraintViolationList([new ConstraintViolation('Quantity must be a positive number.', '', [], '', '', '')])
    //         );
    //     $validViolationList = new ConstraintViolationList();
    //     $invalidViolationList = new ConstraintViolationList([
    //         new ConstraintViolation(
    //             'Quantity must be a positive number.', // error message
    //             null,           // message template
    //             [],             // message parameters
    //             '',             // root
    //             '',             // property path
    //             '',             // invalid value
    //             null,           // plural
    //             null            // code
    //         )
    //     ]);

    //     $this->validator->expects($this->exactly(2))
    //         ->method('validate')
    //         ->willReturnOnConsecutiveCalls($validViolationList, $invalidViolationList);
    //     $this->entityManager->expects($this->once())
    //         ->method('persist')
    //         ->with($this->isInstanceOf(FoodItem::class));

    //     $this->entityManager->expects($this->once())
    //         ->method('flush');

    //     $result = $this->foodItemService->processRequestJson($data, $this->validator);
    //     $this->assertIsArray($result);
    //     $this->assertArrayHasKey('processed', $result);
    //     $this->assertArrayHasKey('errors', $result);
    //     $this->assertTrue($result['successful']);
    //         $this->assertCount(1, $result['processed']['fruits']);
    //         $this->assertCount(0, $result['processed']['vegetables']);
    // }
   

    // public function testSearch(): void
    // {
    //     $criteria = ['name' => 'Banana'];
    //     $foodItem = (new FoodItem())->setName('Banana')->setType('fruit')->setQuantity(1000);
    //     $this->foodItemRepository->expects($this->once())
    //         ->method('findFruitsByCriteria')
    //         ->with($criteria)
    //         ->willReturn([$foodItem]);
    //     $this->foodItemRepository->expects($this->once())
    //         ->method('findVegetablesByCriteria')
    //         ->with($criteria)
    //         ->willReturn([]);
    //     $result = $this->foodItemService->search($criteria);

    //     $this->assertIsArray($result);
    //     $this->assertArrayHasKey('fruits', $result);
    //     $this->assertArrayHasKey('vegetables', $result);
    //     $this->assertCount(1, $result['fruits']);
    //     $this->assertCount(0, $result['vegetables']);
    // }
}    
?>