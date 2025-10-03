<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    public function testAggregateOrdersSuccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/aggregate', [
            'group_by' => 'month',
            'page' => 1,
            'per_page' => 20
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('data', $response);

        $this->assertArrayHasKey('page', $response['meta']);
        $this->assertArrayHasKey('per_page', $response['meta']);
        $this->assertArrayHasKey('total_pages', $response['meta']);
        $this->assertArrayHasKey('total_items', $response['meta']);

        $this->assertEquals(1, $response['meta']['page']);
        $this->assertEquals(20, $response['meta']['per_page']);
        $this->assertIsArray($response['data']);
    }

    public function testAggregateOrdersInvalidGroupBy(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/aggregate', [
            'group_by' => 'invalid',
            'page' => 1,
            'per_page' => 20
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid or missing group_by parameter', $response['error']);
    }

    public function testAggregateOrdersMissingGroupBy(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/aggregate', [
            'page' => 1,
            'per_page' => 20
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid or missing group_by parameter', $response['error']);
    }

    public function testAggregateOrdersInvalidDateRange(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/aggregate', [
            'group_by' => 'month',
            'from_date' => '2025-12-01',
            'to_date' => '2025-01-01'
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid date range', $response['error']);
    }

    public function testGetOrderById(): void
    {
        $client = static::createClient();

        // This will likely return 404 since no orders exist in test database
        $client->request('GET', '/api/orders/1');

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Order not found', $response['error']);
    }

    public function testGetOrderByHash(): void
    {
        $client = static::createClient();

        // This will likely return 404 since no orders exist in test database
        $client->request('GET', '/api/orders/test-hash', [
            'by' => 'hash'
        ]);

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Order not found', $response['error']);
    }

    public function testGetOrderByHashInvalidParameter(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/test-hash', [
            'by' => 'invalid'
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid parameter', $response['error']);
    }
}
