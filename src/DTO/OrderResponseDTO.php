<?php

declare(strict_types=1);

namespace App\DTO;

class OrderResponseDTO
{
    /**
     * @param array<int, array<string, mixed>> $articles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $hash,
        public readonly ?int $userId,
        public readonly string $token,
        public readonly ?string $number,
        public readonly int $status,
        public readonly ?string $email,
        public readonly int $vatType,
        public readonly ?string $vatNumber,
        public readonly ?int $discount,
        public readonly ?string $deliveryPrice,
        public readonly int $deliveryType,
        public readonly ?string $deliveryIndex,
        public readonly ?int $deliveryCountry,
        public readonly ?string $deliveryRegion,
        public readonly ?string $deliveryCity,
        public readonly ?string $deliveryAddress,
        public readonly ?string $deliveryPhone,
        public readonly ?string $clientName,
        public readonly ?string $clientSurname,
        public readonly ?string $companyName,
        public readonly int $payType,
        public readonly ?\DateTimeInterface $payDateExecution,
        public readonly ?\DateTimeInterface $proposedDate,
        public readonly ?\DateTimeInterface $shipDate,
        public readonly ?string $trackingNumber,
        public readonly ?string $managerName,
        public readonly ?string $managerEmail,
        public readonly string $locale,
        public readonly string $curRate,
        public readonly string $currency,
        public readonly string $measure,
        public readonly string $name,
        public readonly ?string $description,
        public readonly \DateTimeInterface $createdAt,
        public readonly \DateTimeInterface $updatedAt,
        public readonly ?array $warehouseData,
        public readonly bool $addressEqual,
        public readonly bool $acceptPay,
        public readonly ?string $weightGross,
        public readonly bool $paymentEuro,
        public readonly bool $specPrice,
        public readonly ?string $deliveryApartmentOffice,
        public readonly array $articles
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'hash' => $this->hash,
            'user_id' => $this->userId,
            'token' => $this->token,
            'number' => $this->number,
            'status' => $this->status,
            'email' => $this->email,
            'vat_type' => $this->vatType,
            'vat_number' => $this->vatNumber,
            'discount' => $this->discount,
            'delivery_price' => $this->deliveryPrice,
            'delivery_type' => $this->deliveryType,
            'delivery_index' => $this->deliveryIndex,
            'delivery_country' => $this->deliveryCountry,
            'delivery_region' => $this->deliveryRegion,
            'delivery_city' => $this->deliveryCity,
            'delivery_address' => $this->deliveryAddress,
            'delivery_phone' => $this->deliveryPhone,
            'client_name' => $this->clientName,
            'client_surname' => $this->clientSurname,
            'company_name' => $this->companyName,
            'pay_type' => $this->payType,
            'pay_date_execution' => $this->payDateExecution?->format('c'),
            'proposed_date' => $this->proposedDate?->format('c'),
            'ship_date' => $this->shipDate?->format('c'),
            'tracking_number' => $this->trackingNumber,
            'manager_name' => $this->managerName,
            'manager_email' => $this->managerEmail,
            'locale' => $this->locale,
            'cur_rate' => $this->curRate,
            'currency' => $this->currency,
            'measure' => $this->measure,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'warehouse_data' => $this->warehouseData,
            'address_equal' => $this->addressEqual,
            'accept_pay' => $this->acceptPay,
            'weight_gross' => $this->weightGross,
            'payment_euro' => $this->paymentEuro,
            'spec_price' => $this->specPrice,
            'delivery_apartment_office' => $this->deliveryApartmentOffice,
            'articles' => $this->articles,
        ];
    }
}
