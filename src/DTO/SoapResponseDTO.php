<?php

declare(strict_types=1);

namespace App\DTO;

class SoapResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $orderId = null,
        public readonly ?string $orderHash = null,
        public readonly ?string $message = null,
        public readonly ?string $error = null
    ) {
    }

    public function toSoapXml(): string
    {
        if ($this->success) {
            return $this->generateSuccessSoapResponse();
        } else {
            return $this->generateErrorSoapResponse();
        }
    }

    private function generateSuccessSoapResponse(): string
    {
        $soapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>';
        $soapEnvelope .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
        $soapEnvelope .= '<soap:Body>';
        $soapEnvelope .= '<createOrderResponse xmlns="http://orders.example.com/soap">';
        $soapEnvelope .= '<result>success</result>';
        $soapEnvelope .= '<orderId>' . htmlspecialchars((string) $this->orderId) . '</orderId>';
        $soapEnvelope .= '<orderHash>' . htmlspecialchars((string) $this->orderHash) . '</orderHash>';
        $soapEnvelope .= '<message>' . htmlspecialchars((string) $this->message) . '</message>';
        $soapEnvelope .= '</createOrderResponse>';
        $soapEnvelope .= '</soap:Body>';
        $soapEnvelope .= '</soap:Envelope>';

        return $soapEnvelope;
    }

    private function generateErrorSoapResponse(): string
    {
        $soapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>';
        $soapEnvelope .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
        $soapEnvelope .= '<soap:Body>';
        $soapEnvelope .= '<soap:Fault>';
        $soapEnvelope .= '<faultcode>soap:Server</faultcode>';
        $soapEnvelope .= '<faultstring>' . htmlspecialchars((string) $this->error) . '</faultstring>';
        $soapEnvelope .= '<detail>';
        $soapEnvelope .= '<errorMessage>' . htmlspecialchars((string) $this->message) . '</errorMessage>';
        $soapEnvelope .= '</detail>';
        $soapEnvelope .= '</soap:Fault>';
        $soapEnvelope .= '</soap:Body>';
        $soapEnvelope .= '</soap:Envelope>';

        return $soapEnvelope;
    }
}
