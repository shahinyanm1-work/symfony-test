<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\OrderResponseDTO;
use App\DTO\SoapResponseDTO;
use App\Exception\OrderCreationException;
use App\Exception\OrderValidationException;
use App\Service\OrderService;
use App\Service\SoapParserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/soap', name: 'api_soap_')]
class SoapController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly SoapParserService $soapParserService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/orders', name: 'create_order', methods: ['POST'])]
    #[OA\Post(
        path: '/api/soap/orders',
        summary: 'Create order via SOAP',
        description: 'Create a new order using SOAP XML format',
        tags: ['SOAP', 'Orders'],
        requestBody: new OA\RequestBody(
            description: 'SOAP XML request',
            required: true,
            content: new OA\MediaType(
                mediaType: 'text/xml',
                schema: new OA\Schema(type: 'string', example: '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <createOrder>
            <client_name>John</client_name>
            <client_surname>Doe</client_surname>
            <email>john@example.com</email>
            <items>
                <item>
                    <article_id>1001</article_id>
                    <amount>10</amount>
                    <price>25.99</price>
                </item>
            </items>
        </createOrder>
    </soap:Body>
</soap:Envelope>')
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order created successfully',
                content: new OA\MediaType(
                    mediaType: 'text/xml',
                    schema: new OA\Schema(type: 'string', example: '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <createOrderResponse>
            <result>success</result>
            <orderId>1</orderId>
            <orderHash>abc123</orderHash>
            <message>Order created successfully</message>
        </createOrderResponse>
    </soap:Body>
</soap:Envelope>')
                )
            ),
            new OA\Response(
                response: 500,
                description: 'SOAP Fault',
                content: new OA\MediaType(
                    mediaType: 'text/xml',
                    schema: new OA\Schema(type: 'string', example: '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>soap:Server</faultcode>
            <faultstring>Validation error</faultstring>
            <detail>
                <errorMessage>Invalid parameters</errorMessage>
            </detail>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>')
                )
            )
        ]
    )]
    public function createOrder(Request $request): Response
    {
        try {
            // Check content type
            $contentType = $request->headers->get('Content-Type', '');
            if (!str_contains($contentType, 'text/xml') && !str_contains($contentType, 'application/xml')) {
                return $this->createSoapFaultResponse(
                    'Invalid content type',
                    'Content-Type must be text/xml or application/xml'
                );
            }

            // Get XML content
            $xmlContent = $request->getContent();
            if (empty($xmlContent)) {
                return $this->createSoapFaultResponse(
                    'Empty request body',
                    'Request body cannot be empty'
                );
            }

            $this->logger->info('SOAP create order request received', [
                'content_length' => strlen($xmlContent),
                'content_type' => $contentType
            ]);

            // Parse SOAP request
            $soapRequest = $this->soapParserService->parseCreateOrderRequest($xmlContent);

            // Create order
            $orderResponse = $this->orderService->createOrder($soapRequest->toOrderDTO());

            $this->logger->info('SOAP order created successfully', [
                'order_id' => $orderResponse->id,
                'order_hash' => $orderResponse->hash,
                'client_name' => $soapRequest->getFullClientName(),
                'items_count' => count($soapRequest->items)
            ]);

            // Create SOAP response
            $soapResponse = new SoapResponseDTO(
                success: true,
                orderId: (string) $orderResponse->id,
                orderHash: $orderResponse->hash,
                message: 'Order created successfully'
            );

            return new Response(
                $soapResponse->toSoapXml(),
                200,
                ['Content-Type' => 'text/xml; charset=utf-8']
            );

        } catch (OrderValidationException $e) {
            $this->logger->warning('SOAP order validation failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($request->getContent())
            ]);

            return $this->createSoapFaultResponse(
                'Validation error',
                $e->getMessage()
            );

        } catch (OrderCreationException $e) {
            $this->logger->error('SOAP order creation failed', [
                'error' => $e->getMessage(),
                'content_length' => strlen($request->getContent())
            ]);

            return $this->createSoapFaultResponse(
                'Order creation error',
                $e->getMessage()
            );

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in SOAP order creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_length' => strlen($request->getContent())
            ]);

            return $this->createSoapFaultResponse(
                'Internal server error',
                'An unexpected error occurred while creating the order'
            );
        }
    }

    private function createSoapFaultResponse(string $error, string $message): Response
    {
        $soapResponse = new SoapResponseDTO(
            success: false,
            error: $error,
            message: $message
        );

        return new Response(
            $soapResponse->toSoapXml(),
            500,
            ['Content-Type' => 'text/xml; charset=utf-8']
        );
    }
}
