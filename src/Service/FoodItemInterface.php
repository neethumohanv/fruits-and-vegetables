<?php

namespace App\Service;
use App\Entity\FoodItem;

interface FoodItemInterface {
    public function addFoodItem(array $foodItem): FoodItem;
    public function listFoodItemsByType(string $unit = 'grams'): array;
    public function deleteFoodItemById(int $id): bool;
}