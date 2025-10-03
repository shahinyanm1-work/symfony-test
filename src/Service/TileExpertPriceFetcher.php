<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PriceResponseDTO;
use App\Exception\PriceFetcherException;
use App\Exception\PriceNotFoundException;
use App\Interface\PriceFetcherInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class TileExpertPriceFetcher implements PriceFetcherInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    private const BASE_URL = 'https://tile.expert';
    private const URL_TEMPLATE = '/fr/tile/%s/%s/a/%s';

    public function __construct(
        private readonly Client $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly int $cacheTtl = self::CACHE_TTL
    ) {
    }

    public function fetchPrice(string $factory, string $collection, string $article): PriceResponseDTO
    {
        $url = $this->buildUrl($factory, $collection, $article);
        $cacheKey = $this->buildCacheKey($factory, $collection, $article);

        // Try to get from cache first
        $cachedItem = $this->cache->getItem($cacheKey);
        if ($cachedItem->isHit()) {
            $this->logger->info('Price fetched from cache', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
                'url' => $url
            ]);
            return $cachedItem->get();
        }

        try {
            $this->logger->info('Fetching price from external source', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
                'url' => $url
            ]);

            $response = $this->httpClient->get($url, [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                ]
            ]);

            $html = $response->getBody()->getContents();
            $priceData = $this->parsePriceFromHtml($html, $factory, $collection, $article, $url);

            // Cache the result
            $cachedItem->set($priceData);
            $cachedItem->expiresAfter($this->cacheTtl);
            $this->cache->save($cachedItem);

            $this->logger->info('Price successfully fetched and cached', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
                'price' => $priceData->price,
                'currency' => $priceData->currency
            ]);

            return $priceData;

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch price from external source', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            throw new PriceFetcherException('Failed to fetch price from external source: ' . $e->getMessage(), $e);
        }
    }

    public function supports(string $source): bool
    {
        return $source === 'tile.expert';
    }

    private function buildUrl(string $factory, string $collection, string $article): string
    {
        return self::BASE_URL . sprintf(self::URL_TEMPLATE, $factory, $collection, $article);
    }

    private function buildCacheKey(string $factory, string $collection, string $article): string
    {
        return 'price_' . md5($factory . '_' . $collection . '_' . $article);
    }

    private function parsePriceFromHtml(string $html, string $factory, string $collection, string $article, string $url): PriceResponseDTO
    {
        try {
            $crawler = new Crawler($html);

            // Try multiple selectors for price
            $priceSelectors = [
                '.price',
                '.product-price',
                '.price-value',
                '[data-price]',
                '.cost',
                '.amount',
                '.price-current',
                '.current-price',
                '.price-amount'
            ];

            $priceText = null;
            foreach ($priceSelectors as $selector) {
                $priceElement = $crawler->filter($selector)->first();
                if ($priceElement->count() > 0) {
                    $priceText = $priceElement->text();
                    break;
                }
            }

            // If no price found with selectors, try to find price in text content
            if (!$priceText) {
                $priceText = $this->extractPriceFromText($html);
            }

            if (!$priceText) {
                throw new PriceNotFoundException('Price not found on the page');
            }

            $price = $this->parsePriceValue($priceText);
            $currency = $this->extractCurrency($priceText) ?: 'EUR';

            return new PriceResponseDTO(
                price: $price,
                currency: $currency,
                factory: $factory,
                collection: $collection,
                article: $article,
                fetchedAt: new \DateTimeImmutable(),
                sourceUrl: $url
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to parse price from HTML', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            throw new PriceNotFoundException('Failed to parse price from page: ' . $e->getMessage(), $e);
        }
    }

    private function extractPriceFromText(string $html): ?string
    {
        // Look for price patterns in the HTML
        $patterns = [
            '/€\s*([0-9]+[,.]?[0-9]*)/i',
            '/EUR\s*([0-9]+[,.]?[0-9]*)/i',
            '/€\s*([0-9]+[,.]?[0-9]*)\s*€/i',
            '/([0-9]+[,.]?[0-9]*)\s*€/i',
            '/price[:\s]*([0-9]+[,.]?[0-9]*)/i',
            '/cost[:\s]*([0-9]+[,.]?[0-9]*)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1] ?? $matches[0];
            }
        }

        return null;
    }

    private function parsePriceValue(string $priceText): float
    {
        // Remove currency symbols and extra whitespace
        $cleanPrice = preg_replace('/[€$£¥]/', '', $priceText);
        $cleanPrice = preg_replace('/[^\d,.-]/', '', $cleanPrice);
        $cleanPrice = trim($cleanPrice);

        // Handle different decimal separators
        if (str_contains($cleanPrice, ',')) {
            // European format: 1.234,56
            $cleanPrice = str_replace('.', '', $cleanPrice);
            $cleanPrice = str_replace(',', '.', $cleanPrice);
        }

        $price = (float) $cleanPrice;

        if ($price <= 0) {
            throw new PriceNotFoundException('Invalid price value: ' . $priceText);
        }

        return $price;
    }

    private function extractCurrency(string $priceText): ?string
    {
        if (stripos($priceText, '€') !== false || stripos($priceText, 'EUR') !== false) {
            return 'EUR';
        }
        if (stripos($priceText, '$') !== false || stripos($priceText, 'USD') !== false) {
            return 'USD';
        }
        if (stripos($priceText, '£') !== false || stripos($priceText, 'GBP') !== false) {
            return 'GBP';
        }

        return null;
    }
}
