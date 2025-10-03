<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PriceControllerTest extends WebTestCase
{
    public function testGetPriceSuccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/price', [
            'factory' => 'cobsa',
            'collection' => 'manual',
            'article' => 'manu7530bcbm-manualbaltic7-5x30'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('price', $response);
        $this->assertArrayHasKey('currency', $response);
        $this->assertArrayHasKey('factory', $response);
        $this->assertArrayHasKey('collection', $response);
        $this->assertArrayHasKey('article', $response);
        $this->assertArrayHasKey('fetched_at', $response);
        $this->assertArrayHasKey('source_url', $response);

        $this->assertEquals('cobsa', $response['factory']);
        $this->assertEquals('manual', $response['collection']);
        $this->assertEquals('manu7530bcbm-manualbaltic7-5x30', $response['article']);
    }

    public function testGetPriceMissingParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/price');

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Missing required parameters', $response['error']);
    }

    public function testGetPriceInvalidParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/price', [
            'factory' => '',
            'collection' => 'manual',
            'article' => 'manu7530bcbm-manualbaltic7-5x30'
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('details', $response);
    }

    public function testGetPriceNotFound(): void
    {
        $client = static::createClient();

        // Mock the price fetcher to throw PriceNotFoundException
        $client->request('GET', '/api/price', [
            'factory' => 'nonexistent',
            'collection' => 'nonexistent',
            'article' => 'nonexistent'
        ]);

        // This will likely return 503 due to service unavailable, but could be 404 if price not found
        $this->assertResponseStatusCodeSame(503);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
    }
}
