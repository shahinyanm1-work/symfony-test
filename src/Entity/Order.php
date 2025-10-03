<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\Index(columns: ['created_at'], name: 'IDX_created_at')]
#[ORM\Index(columns: ['status'], name: 'IDX_status')]
#[ORM\Index(columns: ['email'], name: 'IDX_email')]
#[ORM\Index(columns: ['currency'], name: 'IDX_currency')]
#[ORM\Index(columns: ['client_name', 'client_surname'], name: 'IDX_client_name')]
#[ORM\Index(columns: ['company_name'], name: 'IDX_company_name')]
#[ORM\Index(columns: ['number'], name: 'IDX_number')]
#[ORM\UniqueConstraint(name: 'uniq_hash', columns: ['hash'])]
#[ORM\UniqueConstraint(name: 'uniq_uuid', columns: ['uuid'])]
class Order
{
    // Order status constants
    public const STATUS_PENDING = 1;
    public const STATUS_CONFIRMED = 2;
    public const STATUS_SHIPPED = 3;
    public const STATUS_DELIVERED = 4;
    public const STATUS_CANCELLED = 5;

    // VAT type constants
    public const VAT_TYPE_NONE = 0;
    public const VAT_TYPE_INDIVIDUAL = 1;
    public const VAT_TYPE_COMPANY = 2;

    // Delivery type constants
    public const DELIVERY_TYPE_STANDARD = 0;
    public const DELIVERY_TYPE_EXPRESS = 1;
    public const DELIVERY_TYPE_PICKUP = 2;

    // Payment type constants
    public const PAY_TYPE_CARD = 0;
    public const PAY_TYPE_BANK_TRANSFER = 1;
    public const PAY_TYPE_CASH = 2;
    public const PAY_TYPE_PAYPAL = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ?string $uuid = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private ?string $hash = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private ?string $token = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $number = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED])]
    private int $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 150)]
    private ?string $email = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Choice(choices: [self::VAT_TYPE_NONE, self::VAT_TYPE_INDIVIDUAL, self::VAT_TYPE_COMPANY])]
    private int $vatType = self::VAT_TYPE_NONE;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    private ?string $vatNumber = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $discount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $deliveryPrice = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Choice(choices: [self::DELIVERY_TYPE_STANDARD, self::DELIVERY_TYPE_EXPRESS, self::DELIVERY_TYPE_PICKUP])]
    private int $deliveryType = self::DELIVERY_TYPE_STANDARD;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $deliveryIndex = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $deliveryCountry = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $deliveryRegion = null;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    #[Assert\Length(max: 200)]
    private ?string $deliveryCity = null;

    #[ORM\Column(type: Types::STRING, length: 300, nullable: true)]
    #[Assert\Length(max: 300)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $deliveryPhone = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $clientName = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $clientSurname = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Choice(choices: [self::PAY_TYPE_CARD, self::PAY_TYPE_BANK_TRANSFER, self::PAY_TYPE_CASH, self::PAY_TYPE_PAYPAL])]
    private int $payType = self::PAY_TYPE_CARD;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $payDateExecution = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $proposedDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $shipDate = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $trackingNumber = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $managerName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 100)]
    private ?string $managerEmail = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    private string $locale = 'en';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 6)]
    #[Assert\PositiveOrZero]
    private string $curRate = '1.000000';

    #[ORM\Column(type: Types::STRING, length: 3)]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: Types::STRING, length: 5)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 5)]
    private string $measure = 'm';

    #[ORM\Column(type: Types::STRING, length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $warehouseData = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $addressEqual = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $acceptPay = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $weightGross = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $paymentEuro = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $specPrice = false;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $deliveryApartmentOffice = null;

    /**
     * @var Collection<int, OrderArticle>
     */
    #[ORM\OneToMany(targetEntity: OrderArticle::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getVatType(): int
    {
        return $this->vatType;
    }

    public function setVatType(int $vatType): static
    {
        $this->vatType = $vatType;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getDeliveryPrice(): ?string
    {
        return $this->deliveryPrice;
    }

    public function setDeliveryPrice(?string $deliveryPrice): static
    {
        $this->deliveryPrice = $deliveryPrice;

        return $this;
    }

    public function getDeliveryType(): int
    {
        return $this->deliveryType;
    }

    public function setDeliveryType(int $deliveryType): static
    {
        $this->deliveryType = $deliveryType;

        return $this;
    }

    public function getDeliveryIndex(): ?string
    {
        return $this->deliveryIndex;
    }

    public function setDeliveryIndex(?string $deliveryIndex): static
    {
        $this->deliveryIndex = $deliveryIndex;

        return $this;
    }

    public function getDeliveryCountry(): ?int
    {
        return $this->deliveryCountry;
    }

    public function setDeliveryCountry(?int $deliveryCountry): static
    {
        $this->deliveryCountry = $deliveryCountry;

        return $this;
    }

    public function getDeliveryRegion(): ?string
    {
        return $this->deliveryRegion;
    }

    public function setDeliveryRegion(?string $deliveryRegion): static
    {
        $this->deliveryRegion = $deliveryRegion;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(?string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryPhone(): ?string
    {
        return $this->deliveryPhone;
    }

    public function setDeliveryPhone(?string $deliveryPhone): static
    {
        $this->deliveryPhone = $deliveryPhone;

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): static
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function getClientSurname(): ?string
    {
        return $this->clientSurname;
    }

    public function setClientSurname(?string $clientSurname): static
    {
        $this->clientSurname = $clientSurname;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getPayType(): int
    {
        return $this->payType;
    }

    public function setPayType(int $payType): static
    {
        $this->payType = $payType;

        return $this;
    }

    public function getPayDateExecution(): ?\DateTimeInterface
    {
        return $this->payDateExecution;
    }

    public function setPayDateExecution(?\DateTimeInterface $payDateExecution): static
    {
        $this->payDateExecution = $payDateExecution;

        return $this;
    }

    public function getProposedDate(): ?\DateTimeInterface
    {
        return $this->proposedDate;
    }

    public function setProposedDate(?\DateTimeInterface $proposedDate): static
    {
        $this->proposedDate = $proposedDate;

        return $this;
    }

    public function getShipDate(): ?\DateTimeInterface
    {
        return $this->shipDate;
    }

    public function setShipDate(?\DateTimeInterface $shipDate): static
    {
        $this->shipDate = $shipDate;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getManagerName(): ?string
    {
        return $this->managerName;
    }

    public function setManagerName(?string $managerName): static
    {
        $this->managerName = $managerName;

        return $this;
    }

    public function getManagerEmail(): ?string
    {
        return $this->managerEmail;
    }

    public function setManagerEmail(?string $managerEmail): static
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getCurRate(): string
    {
        return $this->curRate;
    }

    public function setCurRate(string $curRate): static
    {
        $this->curRate = $curRate;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMeasure(): string
    {
        return $this->measure;
    }

    public function setMeasure(string $measure): static
    {
        $this->measure = $measure;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getWarehouseData(): ?array
    {
        return $this->warehouseData;
    }

    public function setWarehouseData(?array $warehouseData): static
    {
        $this->warehouseData = $warehouseData;

        return $this;
    }

    public function isAddressEqual(): bool
    {
        return $this->addressEqual;
    }

    public function setAddressEqual(bool $addressEqual): static
    {
        $this->addressEqual = $addressEqual;

        return $this;
    }

    public function isAcceptPay(): bool
    {
        return $this->acceptPay;
    }

    public function setAcceptPay(bool $acceptPay): static
    {
        $this->acceptPay = $acceptPay;

        return $this;
    }

    public function getWeightGross(): ?string
    {
        return $this->weightGross;
    }

    public function setWeightGross(?string $weightGross): static
    {
        $this->weightGross = $weightGross;

        return $this;
    }

    public function isPaymentEuro(): bool
    {
        return $this->paymentEuro;
    }

    public function setPaymentEuro(bool $paymentEuro): static
    {
        $this->paymentEuro = $paymentEuro;

        return $this;
    }

    public function isSpecPrice(): bool
    {
        return $this->specPrice;
    }

    public function setSpecPrice(bool $specPrice): static
    {
        $this->specPrice = $specPrice;

        return $this;
    }

    public function getDeliveryApartmentOffice(): ?string
    {
        return $this->deliveryApartmentOffice;
    }

    public function setDeliveryApartmentOffice(?string $deliveryApartmentOffice): static
    {
        $this->deliveryApartmentOffice = $deliveryApartmentOffice;

        return $this;
    }

    /**
     * @return Collection<int, OrderArticle>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(OrderArticle $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setOrder($this);
        }

        return $this;
    }

    public function removeArticle(OrderArticle $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getOrder() === $this) {
                $article->setOrder(null);
            }
        }

        return $this;
    }

    public function getFullClientName(): string
    {
        return trim(($this->clientName ?? '') . ' ' . ($this->clientSurname ?? ''));
    }

    public function getTotalAmount(): float
    {
        $total = 0;
        foreach ($this->articles as $article) {
            $total += floatval($article->getPrice()) * floatval($article->getAmount());
        }
        return $total;
    }

    public function getTotalWeight(): float
    {
        $total = 0;
        foreach ($this->articles as $article) {
            if ($article->getWeight()) {
                $total += floatval($article->getWeight()) * floatval($article->getAmount());
            }
        }
        return $total;
    }
}
