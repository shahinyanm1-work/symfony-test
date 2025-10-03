<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PriceResponseDTO;
use App\Exception\PriceFetcherException;
use App\Interface\PriceFetcherInterface;
use Psr\Log\LoggerInterface;

class PriceFetcherService
{
    public function __construct(
        /** @var iterable<PriceFetcherInterface> $fetchers */
        private readonly iterable $fetchers,
        private readonly LoggerInterface $logger
    ) {
    }

    public function fetchPrice(string $factory, string $collection, string $article, string $source = 'tile.expert'): PriceResponseDTO
    {
        $this->logger->info('Attempting to fetch price', [
            'factory' => $factory,
            'collection' => $collection,
            'article' => $article,
            'source' => $source
        ]);

        foreach ($this->fetchers as $fetcher) {
            if ($fetcher->supports($source)) {
                try {
                    return $fetcher->fetchPrice($factory, $collection, $article);
                } catch (\Exception $e) {
                    $this->logger->warning('Price fetcher failed, trying next', [
                        'fetcher' => get_class($fetcher),
                        'source' => $source,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
        }

        throw new PriceFetcherException('No price fetcher available for source: ' . $source);
    }

    public function getSupportedSources(): array
    {
        $sources = [];
        foreach ($this->fetchers as $fetcher) {
            if ($fetcher instanceof TileExpertPriceFetcher) {
                $sources[] = 'tile.expert';
            }
        }
        return $sources;
    }
}
