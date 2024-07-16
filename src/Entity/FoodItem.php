<?php

namespace App\Entity;

use App\Repository\FoodItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Exception\ValidationException;

#[ORM\Entity(repositoryClass: FoodItemRepository::class)]
class FoodItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]    
    #[Groups('food_item:read')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Groups('food_item:read')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Choice(choices: ['fruit', 'vegetable'])]
    #[Groups('food_item:read')]
    private ?string $type = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank()]
    #[Assert\Positive()]
    #[Groups('food_item:read')]
    private ?float $quantity  = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }
    
    public function setQuantity($quantity, string $unit = 'grams'): static
    {
        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new ValidationException('Quantity must be a positive number.');
        }    
        $this->quantity = $this->convertToGrams((float)$quantity, $unit);

        return $this;
    }

    private function convertToGrams(float $quantity, string $unit): float
    {
        switch ($unit) {
            case 'kg':
                return $quantity * 1000;
            default:
                return $quantity;
        }
    }
}
