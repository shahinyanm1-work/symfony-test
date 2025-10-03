<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\PriceResponseDTO;
use App\Exception\PriceFetcherException;
use App\Exception\PriceNotFoundException;
use App\Service\TileExpertPriceFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class TileExpertPriceFetcherTest extends TestCase
{
    private TileExpertPriceFetcher $priceFetcher;
    private Client $mockClient;
    private CacheItemPoolInterface $mockCache;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(Client::class);
        $this->mockCache = $this->createMock(CacheItemPoolInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->priceFetcher = new TileExpertPriceFetcher(
            $this->mockClient,
            $this->mockCache,
            $this->mockLogger,
            3600
        );
    }

    public function testSupportsTileExpert(): void
    {
        $this->assertTrue($this->priceFetcher->supports('tile.expert'));
        $this->assertFalse($this->priceFetcher->supports('other.source'));
    }

    public function testFetchPriceSuccess(): void
    {
        // Mock cache miss
        $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->willReturnSelf();
        $cacheItem->expects($this->once())->method('expiresAfter')->willReturnSelf();

        $this->mockCache->expects($this->once())
            ->method('getItem')
            ->with('price_' . md5('factory_collection_article'))
            ->willReturn($cacheItem);

        $this->mockCache->expects($this->once())->method('save');

        // Mock HTTP response
        $html = '<div class="price">€25.99</div>';
        $response = new Response(200, [], $html);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('https://tile.expert/fr/tile/factory/collection/a/article')
            ->willReturn($response);

        $result = $this->priceFetcher->fetchPrice('factory', 'collection', 'article');

        $this->assertInstanceOf(PriceResponseDTO::class, $result);
        $this->assertEquals(25.99, $result->price);
        $this->assertEquals('EUR', $result->currency);
        $this->assertEquals('factory', $result->factory);
        $this->assertEquals('collection', $result->collection);
        $this->assertEquals('article', $result->article);
    }

    public function testFetchPriceFromCache(): void
    {
        $cachedPrice = new PriceResponseDTO(
            price: 25.99,
            currency: 'EUR',
            factory: 'factory',
            collection: 'collection',
            article: 'article',
            fetchedAt: new \DateTimeImmutable(),
            sourceUrl: 'https://tile.expert/fr/tile/factory/collection/a/article'
        );

        $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);
        $cacheItem->expects($this->once())->method('get')->willReturn($cachedPrice);

        $this->mockCache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->mockClient->expects($this->never())->method('get');

        $result = $this->priceFetcher->fetchPrice('factory', 'collection', 'article');

        $this->assertSame($cachedPrice, $result);
    }

    public function testFetchPriceHttpError(): void
    {
        $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);

        $this->mockCache->expects($this->once())->method('getItem')->willReturn($cacheItem);

        $request = new Request('GET', 'https://tile.expert/fr/tile/factory/collection/a/article');
        $exception = new RequestException('Connection failed', $request);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $this->expectException(PriceFetcherException::class);
        $this->expectExceptionMessage('Failed to fetch price from external source');

        $this->priceFetcher->fetchPrice('factory', 'collection', 'article');
    }

    public function testFetchPriceNotFound(): void
    {
        $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);

        $this->mockCache->expects($this->once())->method('getItem')->willReturn($cacheItem);

        // Mock response with no price
        $html = '<div>No price information available</div>';
        $response = new Response(200, [], $html);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->expectException(PriceNotFoundException::class);
        $this->expectExceptionMessage('Price not found on the page');

        $this->priceFetcher->fetchPrice('factory', 'collection', 'article');
    }

    public function testParsePriceWithDifferentFormats(): void
    {
        $testCases = [
            ['€25.99', 25.99, 'EUR'],
            ['25,99 €', 25.99, 'EUR'],
            ['$15.50', 15.50, 'USD'],
            ['15.50', 15.50, null],
            ['EUR 42.00', 42.00, 'EUR'],
        ];

        foreach ($testCases as [$priceText, $expectedPrice, $expectedCurrency]) {
            $cacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
            $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
            $cacheItem->expects($this->once())->method('set')->willReturnSelf();
            $cacheItem->expects($this->once())->method('expiresAfter')->willReturnSelf();

            $this->mockCache->expects($this->once())->method('getItem')->willReturn($cacheItem);
            $this->mockCache->expects($this->once())->method('save');

            $html = "<div class='price'>{$priceText}</div>";
            $response = new Response(200, [], $html);

            $this->mockClient->expects($this->once())
                ->method('get')
                ->willReturn($response);

            $result = $this->priceFetcher->fetchPrice('factory', 'collection', 'article');

            $this->assertEquals($expectedPrice, $result->price);
            if ($expectedCurrency) {
                $this->assertEquals($expectedCurrency, $result->currency);
            }
        }
    }
}
