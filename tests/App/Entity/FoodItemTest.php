<?php

namespace App\Tests\App\Entity;

use App\Entity\FoodItem;
use PHPUnit\Framework\TestCase;
use App\Exception\ValidationException;

class FoodItemTest extends TestCase
{
    public function testSetQuantityWithValidData(): void
    {
        $foodItem = new FoodItem();
        $foodItem->setQuantity(1, 'kg');
        $this->assertEquals(1000, $foodItem->getQuantity()); // 1 kg converted to grams
    }

    public function testSetQuantityWithDefaultUnit(): void
    {
        $foodItem = new FoodItem();
        $foodItem->setQuantity(500); // Default unit is grams
        $this->assertEquals(500, $foodItem->getQuantity());
    }

    public function testSetQuantityWithInvalidData(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Quantity must be a positive number.');
        $foodItem = new FoodItem();
        $foodItem->setQuantity(-5, 'kg'); // Negative quantity should throw exception
    }

    public function testSetQuantityWithNonNumericData(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Quantity must be a positive number.');
        $foodItem = new FoodItem();
        $foodItem->setQuantity('non-numeric', 'kg'); // Non-numeric quantity should throw exception
    }

    public function testCreateFoodItemWithRequiredFields(): void
    {
        $foodItem = new FoodItem();
        $foodItem->setName('Apple');
        $foodItem->setType('fruit');
        $foodItem->setQuantity(1);
        $this->assertNotEmpty($foodItem->getName());
        $this->assertNotEmpty($foodItem->getType());
        $this->assertEquals(1, $foodItem->getQuantity());
    }
}
