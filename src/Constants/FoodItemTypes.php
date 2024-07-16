<?php
namespace App\Constants;

class FoodItemTypes
{
    public const FRUIT = 'fruit';
    public const VEGETABLE = 'vegetable';

    public static function getAllowedTypes(): array
    {
        return [
            self::FRUIT,
            self::VEGETABLE,
        ];
    }
}

?>