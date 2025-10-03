<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SoapOrderItemDTO
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

    #[Assert\Length(exactly: 3)]
    public ?string $currency = null;

    #[Assert\Length(max: 5)]
    public ?string $measure = null;

    public function getTotalPrice(): float
    {
        return $this->price * $this->amount;
    }
}
