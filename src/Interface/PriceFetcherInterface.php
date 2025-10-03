<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\PriceResponseDTO;

interface PriceFetcherInterface
{
    /**
     * Fetch price for a specific article from external source
     *
     * @param string $factory Factory identifier
     * @param string $collection Collection identifier
     * @param string $article Article identifier
     * @return PriceResponseDTO Price information
     * @throws \App\Exception\PriceNotFoundException When price is not found
     * @throws \App\Exception\PriceFetcherException When fetching fails
     */
    public function fetchPrice(string $factory, string $collection, string $article): PriceResponseDTO;

    /**
     * Check if this fetcher supports the given source
     *
     * @param string $source Source identifier
     * @return bool True if supported, false otherwise
     */
    public function supports(string $source): bool;
}
