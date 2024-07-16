<?php

namespace App\Repository;

use App\Entity\FoodItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Constants\FoodItemTypes;


class FoodItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodItem::class);
    }

    public function save(FoodItem $foodItem): void
    {
        $this->_em->persist($foodItem);
        $this->_em->flush();
    }

    public function findAllFoodItems(): array
    {
        return $this->findAll();
    }

    public function findFruits(): array
    {
        return $this->findBy(['type' => FoodItemTypes::FRUIT]);
    }

    public function findVegetables(): array
    {
        return $this->findBy(['type' => FoodItemTypes::VEGETABLE]);
    }

    public function findFruitsByCriteria(array $criteria): array
    {
        return $this->findByCriteriaAndType($criteria, FoodItemTypes::FRUIT);
    }

    public function findVegetablesByCriteria(array $criteria): array
    {
        return $this->findByCriteriaAndType($criteria, FoodItemTypes::VEGETABLE);
    }


    public function findSearchByCriteria(array $criteria, string $type): array
    {
        $qb = $this->createQueryBuilder('fi')
            ->andWhere('fi.type = :type')
            ->setParameter('type', $type);
        // Apply filters based on criteria collection
        foreach ($criteria as $key => $value) {
            $this->applyFilter($qb, $key, $value);
        }
        return $qb->getQuery()->getResult();
    }

    private function applyFilter(QueryBuilder $qb, string $key, $value): void
    {
        switch ($key) {
            case 'name':
                $qb->andWhere('LOWER(fi.name) LIKE LOWER(:name)')
                    ->setParameter('name', '%' . $value . '%');
                break;
            case 'quantity':
                $this->applyQuantityFilter($qb, $value);
                break;
                // Add more filters as needed
        }
    }

    private function applyQuantityFilter(QueryBuilder $qb, $value): void
    {
        if (preg_match('/^(<|>|=)?(\d+(?:-\d+)?)$/', $value, $matches)) {
            $operator = $matches[1] ?: '=';
            $quantityParts = explode('-', $matches[2]);

            if (count($quantityParts) === 1) {
                $quantity = (float)$quantityParts[0];
                $qb->andWhere("fi.quantity $operator :quantity")
                    ->setParameter('quantity', $quantity);
            } else {
                $quantityLow = (float)$quantityParts[0];
                $quantityHigh = (float)$quantityParts[1];
                $qb->andWhere("fi.quantity BETWEEN :quantityLow AND :quantityHigh")
                    ->setParameter('quantityLow', $quantityLow)
                    ->setParameter('quantityHigh', $quantityHigh);
            }
        } else {
            throw new \InvalidArgumentException('Invalid quantity filter format.');
        }
    }

    public function findById(int $id): ?FoodItem
    {
        return $this->find($id);
    }
    
    public function remove(FoodItem $foodItem): void
    {
        $this->_em->remove($foodItem);
        $this->_em->flush();
    }
}
