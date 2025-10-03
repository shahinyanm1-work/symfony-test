<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SoapControllerTest extends WebTestCase
{
    public function testCreateOrderSuccess(): void
    {
        $client = static::createClient();

        $xmlContent = $this->getValidSoapXml();

        $client->request('POST', '/api/soap/orders', [], [], [
            'CONTENT_TYPE' => 'text/xml',
        ], $xmlContent);

        // This might return 500 if the service is not properly configured for tests
        // or 200 if successful
        $this->assertResponseStatusCodeSame(200);

        $response = $client->getResponse()->getContent();

        $this->assertStringContainsString('soap:Envelope', $response);
        $this->assertStringContainsString('soap:Body', $response);
    }

    public function testCreateOrderInvalidContentType(): void
    {
        $client = static::createClient();

        $xmlContent = $this->getValidSoapXml();

        $client->request('POST', '/api/soap/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $xmlContent);

        $this->assertResponseStatusCodeSame(500);

        $response = $client->getResponse()->getContent();

        $this->assertStringContainsString('soap:Fault', $response);
        $this->assertStringContainsString('Invalid content type', $response);
    }

    public function testCreateOrderEmptyBody(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/soap/orders', [], [], [
            'CONTENT_TYPE' => 'text/xml',
        ], '');

        $this->assertResponseStatusCodeSame(500);

        $response = $client->getResponse()->getContent();

        $this->assertStringContainsString('soap:Fault', $response);
        $this->assertStringContainsString('Empty request body', $response);
    }

    public function testCreateOrderInvalidXml(): void
    {
        $client = static::createClient();

        $invalidXml = '<invalid>xml</content>';

        $client->request('POST', '/api/soap/orders', [], [], [
            'CONTENT_TYPE' => 'text/xml',
        ], $invalidXml);

        $this->assertResponseStatusCodeSame(500);

        $response = $client->getResponse()->getContent();

        $this->assertStringContainsString('soap:Fault', $response);
    }

    private function getValidSoapXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <createOrder>
            <client_name>John</client_name>
            <client_surname>Doe</client_surname>
            <email>john@example.com</email>
            <company_name>Test Company</company_name>
            <description>Test order from SOAP</description>
            <items>
                <item>
                    <article_id>1001</article_id>
                    <article_code>TILE-001</article_code>
                    <article_name>Test Tile</article_name>
                    <amount>10</amount>
                    <price>25.99</price>
                    <currency>EUR</currency>
                    <measure>m2</measure>
                </item>
            </items>
        </createOrder>
    </soap:Body>
</soap:Envelope>';
    }
}
