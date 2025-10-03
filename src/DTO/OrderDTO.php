<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class OrderDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $clientName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $clientSurname = null;

    #[Assert\Email]
    #[Assert\Length(max: 150)]
    public ?string $email = null;

    #[Assert\Length(max: 255)]
    public ?string $companyName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    public ?string $name = null;

    #[Assert\Length(max: 1000)]
    public ?string $description = null;

    #[Assert\Choice(choices: ['EUR', 'USD', 'GBP'])]
    public string $currency = 'EUR';

    #[Assert\Range(min: 0, max: 100)]
    public ?int $discount = null;

    #[Assert\PositiveOrZero]
    public ?float $deliveryPrice = null;

    #[Assert\Choice(choices: [0, 1, 2])]
    public int $deliveryType = 0;

    #[Assert\Length(max: 20)]
    public ?string $deliveryIndex = null;

    #[Assert\Length(max: 100)]
    public ?string $deliveryRegion = null;

    #[Assert\Length(max: 200)]
    public ?string $deliveryCity = null;

    #[Assert\Length(max: 300)]
    public ?string $deliveryAddress = null;

    #[Assert\Length(max: 50)]
    public ?string $deliveryPhone = null;

    #[Assert\Choice(choices: [0, 1, 2, 3])]
    public int $payType = 0;

    #[Assert\Length(max: 10)]
    public string $locale = 'en';

    #[Assert\Length(max: 5)]
    public string $measure = 'm';

    /**
     * @var OrderArticleDTO[]
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    public array $articles = [];

    public function getFullClientName(): string
    {
        return trim(($this->clientName ?? '') . ' ' . ($this->clientSurname ?? ''));
    }

    public function getTotalAmount(): float
    {
        $total = 0;
        foreach ($this->articles as $article) {
            $total += $article->price * $article->amount;
        }
        return $total;
    }
}
