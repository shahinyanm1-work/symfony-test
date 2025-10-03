<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testSearchOrdersSuccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => 'john*',
            'page' => 1,
            'per_page' => 20
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('data', $response);

        $this->assertArrayHasKey('query', $response['meta']);
        $this->assertArrayHasKey('page', $response['meta']);
        $this->assertArrayHasKey('per_page', $response['meta']);
        $this->assertArrayHasKey('total', $response['meta']);
        $this->assertArrayHasKey('total_pages', $response['meta']);

        $this->assertEquals('john*', $response['meta']['query']);
        $this->assertEquals(1, $response['meta']['page']);
        $this->assertEquals(20, $response['meta']['per_page']);
        $this->assertIsArray($response['data']);
    }

    public function testSearchOrdersMissingQuery(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search');

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Missing required parameter', $response['error']);
    }

    public function testSearchOrdersEmptyQuery(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => ''
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Missing required parameter', $response['error']);
    }

    public function testSearchOrdersShortQuery(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => 'a'
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Query must be at least 2 characters long', $response['error']);
    }

    public function testSearchOrdersInvalidCharacters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => 'test<script>alert("xss")</script>'
        ]);

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid query', $response['error']);
    }

    public function testSearchOrdersWithWildcards(): void
    {
        $client = static::createClient();

        $testCases = [
            'john*',
            '*smith',
            '*test*',
            'jo*hn',
            'test*123'
        ];

        foreach ($testCases as $query) {
            $client->request('GET', '/api/orders/search', [
                'q' => $query,
                'page' => 1,
                'per_page' => 20
            ]);

            $this->assertResponseIsSuccessful();

            $response = json_decode($client->getResponse()->getContent(), true);

            $this->assertArrayHasKey('meta', $response);
            $this->assertArrayHasKey('data', $response);
            $this->assertEquals($query, $response['meta']['query']);
        }
    }

    public function testSearchOrdersPagination(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => 'test',
            'page' => 2,
            'per_page' => 5
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['meta']['page']);
        $this->assertEquals(5, $response['meta']['per_page']);
    }

    public function testSearchOrdersInvalidPagination(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => 'test',
            'page' => 0,
            'per_page' => 0
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        // Should default to valid values
        $this->assertEquals(1, $response['meta']['page']);
        $this->assertEquals(20, $response['meta']['per_page']);
    }

    public function testSearchOrdersLargePerPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/orders/search', [
            'q' => 'test',
            'per_page' => 1000
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        // Should be limited to 100
        $this->assertEquals(100, $response['meta']['per_page']);
    }
}
