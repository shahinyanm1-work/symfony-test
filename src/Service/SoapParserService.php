<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SoapCreateOrderRequestDTO;
use App\DTO\SoapOrderItemDTO;
use App\Exception\OrderValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SoapParserService
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function parseCreateOrderRequest(string $xmlContent): SoapCreateOrderRequestDTO
    {
        try {
            // Load XML content
            $xml = new \SimpleXMLElement($xmlContent);

            // Check if it's a valid SOAP envelope
            if (!$this->isValidSoapEnvelope($xml)) {
                throw new OrderValidationException('Invalid SOAP envelope');
            }

            // Extract createOrder element
            $createOrderElement = $this->extractCreateOrderElement($xml);
            if (!$createOrderElement) {
                throw new OrderValidationException('createOrder element not found');
            }

            // Parse the request
            $request = new SoapCreateOrderRequestDTO();
            $request->clientName = $this->getStringValue($createOrderElement, 'client_name');
            $request->clientSurname = $this->getStringValue($createOrderElement, 'client_surname');
            $request->email = $this->getStringValue($createOrderElement, 'email');
            $request->companyName = $this->getStringValue($createOrderElement, 'company_name');
            $request->description = $this->getStringValue($createOrderElement, 'description');

            // Parse items
            $items = $this->parseItems($createOrderElement);
            if (empty($items)) {
                throw new OrderValidationException('No items found in order');
            }

            $request->items = $items;

            // Validate the parsed request
            $violations = $this->validator->validate($request);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                }
                throw new OrderValidationException('Validation failed: ' . implode(', ', $errors));
            }

            $this->logger->info('SOAP request parsed successfully', [
                'client_name' => $request->clientName,
                'client_surname' => $request->clientSurname,
                'email' => $request->email,
                'items_count' => count($request->items)
            ]);

            return $request;

        } catch (\Exception $e) {
            $this->logger->error('Failed to parse SOAP request', [
                'error' => $e->getMessage(),
                'xml_length' => strlen($xmlContent)
            ]);

            if ($e instanceof OrderValidationException) {
                throw $e;
            }

            throw new OrderValidationException('Failed to parse SOAP request: ' . $e->getMessage(), $e);
        }
    }

    private function isValidSoapEnvelope(\SimpleXMLElement $xml): bool
    {
        $namespaces = $xml->getNamespaces(true);
        return isset($namespaces['soap']) || isset($namespaces['']);
    }

    private function extractCreateOrderElement(\SimpleXMLElement $xml): ?\SimpleXMLElement
    {
        // Try to find createOrder element in different possible locations
        $paths = [
            '//createOrder',
            '//soap:Body/createOrder',
            '//Body/createOrder'
        ];

        foreach ($paths as $path) {
            $elements = $xml->xpath($path);
            if (!empty($elements)) {
                return $elements[0];
            }
        }

        return null;
    }

    private function getStringValue(\SimpleXMLElement $element, string $name): ?string
    {
        $children = $element->xpath($name);
        if (!empty($children)) {
            $value = (string) $children[0];
            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function parseItems(\SimpleXMLElement $createOrderElement): array
    {
        $items = [];

        // Try different possible item structures
        $itemPaths = [
            'items/item',
            'item',
            'orderItems/item',
            'products/product'
        ];

        foreach ($itemPaths as $path) {
            $itemElements = $createOrderElement->xpath($path);
            if (!empty($itemElements)) {
                foreach ($itemElements as $itemElement) {
                    $item = $this->parseItem($itemElement);
                    if ($item) {
                        $items[] = $item;
                    }
                }
                break; // Use the first path that returns items
            }
        }

        return $items;
    }

    private function parseItem(\SimpleXMLElement $itemElement): ?SoapOrderItemDTO
    {
        try {
            $item = new SoapOrderItemDTO();
            
            $item->articleId = $this->getIntValue($itemElement, 'article_id') ?: $this->getIntValue($itemElement, 'articleId');
            $item->articleCode = $this->getStringValue($itemElement, 'article_code') ?: $this->getStringValue($itemElement, 'articleCode');
            $item->articleName = $this->getStringValue($itemElement, 'article_name') ?: $this->getStringValue($itemElement, 'articleName');
            $item->amount = $this->getFloatValue($itemElement, 'amount') ?: $this->getFloatValue($itemElement, 'quantity');
            $item->price = $this->getFloatValue($itemElement, 'price') ?: $this->getFloatValue($itemElement, 'cost');
            $item->currency = $this->getStringValue($itemElement, 'currency') ?: 'EUR';
            $item->measure = $this->getStringValue($itemElement, 'measure') ?: 'm';

            // Validate item
            $violations = $this->validator->validate($item);
            if (count($violations) > 0) {
                $this->logger->warning('Invalid item in SOAP request', [
                    'violations' => (string) $violations
                ]);
                return null;
            }

            return $item;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse item in SOAP request', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function getIntValue(\SimpleXMLElement $element, string $name): ?int
    {
        $value = $this->getStringValue($element, $name);
        return $value !== null ? (int) $value : null;
    }

    private function getFloatValue(\SimpleXMLElement $element, string $name): ?float
    {
        $value = $this->getStringValue($element, $name);
        return $value !== null ? (float) $value : null;
    }
}
