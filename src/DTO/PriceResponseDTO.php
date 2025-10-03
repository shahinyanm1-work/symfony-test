<?php

declare(strict_types=1);

namespace App\DTO;

class PriceResponseDTO
{
    public function __construct(
        public readonly float $price,
        public readonly string $currency,
        public readonly string $factory,
        public readonly string $collection,
        public readonly string $article,
        public readonly \DateTimeInterface $fetchedAt,
        public readonly string $sourceUrl
    ) {
    }

    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'currency' => $this->currency,
            'factory' => $this->factory,
            'collection' => $this->collection,
            'article' => $this->article,
            'fetched_at' => $this->fetchedAt->format('c'),
            'source_url' => $this->sourceUrl,
        ];
    }
}
