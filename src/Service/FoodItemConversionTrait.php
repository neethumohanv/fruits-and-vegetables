<?php

namespace App\Service;

trait FoodItemConversionTrait
{
    private function convertQuantities(array $itemsByType, string $unit): array
    {
            $conversionFactor = in_array($unit, ['kg', 'kilograms']) ? 0.001 : 1;
            return array_map(
                fn ($items) => array_map(fn ($item) => $item->setQuantity($item->getQuantity() * $conversionFactor), $items),
                $itemsByType
            );
    }
}
