<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class OrderArticleDTO
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $articleId = null;

    #[Assert\Length(max: 100)]
    public ?string $articleCode = null;

    #[Assert\Length(max: 255)]
    public ?string $articleName = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?float $amount = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?float $price = null;

    #[Assert\PositiveOrZero]
    public ?float $priceEur = null;

    #[Assert\Length(exactly: 3)]
    public ?string $currency = null;

    #[Assert\Length(max: 5)]
    public ?string $measure = null;

    public ?\DateTimeInterface $deliveryTimeMin = null;

    public ?\DateTimeInterface $deliveryTimeMax = null;

    #[Assert\PositiveOrZero]
    public ?float $weight = null;

    #[Assert\PositiveOrZero]
    public ?float $packagingCount = null;

    #[Assert\PositiveOrZero]
    public ?float $pallet = null;

    #[Assert\PositiveOrZero]
    public ?float $packaging = null;

    public bool $swimmingPool = false;

    public function getTotalPrice(): float
    {
        return $this->price * $this->amount;
    }

    public function getTotalWeight(): ?float
    {
        if (!$this->weight) {
            return null;
        }
        return $this->weight * $this->amount;
    }

    public function getDeliveryDays(): ?int
    {
        if (!$this->deliveryTimeMin || !$this->deliveryTimeMax) {
            return null;
        }

        $diff = $this->deliveryTimeMax->diff($this->deliveryTimeMin);
        return $diff->days;
    }
}
