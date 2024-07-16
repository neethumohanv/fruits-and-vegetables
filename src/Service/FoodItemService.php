<?php

namespace App\Service;


use App\Entity\FoodItem;
use App\Repository\FoodItemRepository;
use App\Exception\ValidationException;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException as ExceptionInvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException;


class FoodItemService implements FoodItemInterface
{
    use FoodItemConversionTrait;
    
    private $entityManager;
    private $foodItemRepository;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, FoodItemRepository $foodItemRepository, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->foodItemRepository = $foodItemRepository;
        $this->validator = $validator;
    }


    /**
     * Adds a new food item to the repository.
     *
     * @param array $data The data for the food item
     * @return FoodItem The created food item
     * @throws ValidationException if validation fails
     */
    public function addFoodItem(array $data): FoodItem
    {
        $foodItem = new FoodItem();
        $foodItem->setName($data['name']);
        $foodItem->setType($data['type']);
        $foodItem->setQuantity($data['quantity'], $data['unit'] ?? 'grams');
        $errors = $this->validator->validate($foodItem);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new ValidationException(implode(', ', $errorMessages));
        }
        $this->foodItemRepository->save($foodItem);
        return $foodItem;
    }

    /**
     *
     * @return array The list of food items
     */
    public function listFoodItems(): array
    {
        return $this->foodItemRepository->findAllFoodItems();
    }

     /**
     * Lists food items by type, optionally converting their quantities to a specified unit.
     *
     * @param string $unit The unit to convert quantities to (default is 'grams')
     * @return array
     */
    public function listFoodItemsByType(string $unit = 'grams'): array
    {
        $fruits = $this->foodItemRepository->findFruits();
        $vegetables = $this->foodItemRepository->findVegetables();
        return $this->convertQuantities([
            'fruits' => $fruits,
            'vegetables' => $vegetables,
        ], $unit);
    }

    
    /**
     * Processes a list of food items from JSON data.
     *
     * @param array $data The JSON data to process
     * @param ValidatorInterface $validator
     * @param string|null $type The type of food items (optional)
     * @return array The processed data, including successful items and errors
     */
    public function processRequestJson(array $data, ValidatorInterface $validator, string $type = null): array
    {
        $fruits = [];
        $vegetables = [];
        $errors = [];
        $successful = false;

        foreach ($data as $index => $item) {
            try {
                $foodItem = new FoodItem();
                $foodItem->setName($item['name']);
                $foodItem->setType($type ?? $item['type']);
                $foodItem->setQuantity($item['quantity'], $item['unit'] ?? 'grams');
                $validationErrors = $validator->validate($foodItem);

                if (count($validationErrors) === 0) {
                    $this->entityManager->persist($foodItem);                   
                    $successful = true;  // Mark as successful if at least one item is valid
                    if ($foodItem->getType() === 'fruit') {
                        $fruits[] = $foodItem;
                    } else {
                        $vegetables[] = $foodItem;
                    }
                } else {
                    foreach ($validationErrors as $error) {
                        $errors[] = [
                            'index' => $index,
                            'item' => $item,
                            'error' => $error->getMessage(),
                        ];
                    }
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'item' => $item,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($successful) {
        $this->entityManager->flush();
    }

        return [
            'processed' => [
                'fruits' => $fruits,
                'vegetables' => $vegetables,
            ],
            'errors' => $errors,            
            'successful' => $successful, // Add this to indicate if there was success

        ];
    }

     /**
     *
     * @param int $id
     * @return bool
     */
    public function deleteFoodItemById(int $id): bool
    {
        $foodItem = $this->foodItemRepository->findById($id);
        if (!$foodItem) {
            return false;
        }
        $this->foodItemRepository->remove($foodItem);
        return true;
    }

    /**
     * Searches for food items by type and criteria, optionally converting their quantities to a specified unit.
     *
     * @param string|null $type The type of food items (optional)
     * @param array $criteria The search criteria
     * @param string $unit The unit to convert quantities to (default is 'grams')
     * @return array The search results grouped by type
     */
    public function searchFoodItemsByType(?string $type, array $criteria, string $unit = 'grams'): array
    {
        if ($type) {
            if (!in_array($type, ['fruit', 'vegetable'])) {
                throw new \InvalidArgumentException('Invalid type. Allowed types are "fruit" and "vegetable".');
            }
            return [$type => $this->foodItemRepository->findSearchByCriteria($criteria, $type)];
        }
        $fruits = $this->foodItemRepository->findSearchByCriteria($criteria, 'fruit');
        $vegetables = $this->foodItemRepository->findSearchByCriteria($criteria, 'vegetable');

        return [
            'fruits' => $fruits,
            'vegetables' => $vegetables,
        ];
    }

}
