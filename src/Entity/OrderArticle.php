<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderArticleRepository::class)]
#[ORM\Table(name: 'orders_article')]
#[ORM\Index(columns: ['article_id'], name: 'IDX_article_id')]
#[ORM\Index(columns: ['article_code'], name: 'IDX_article_code')]
#[ORM\Index(columns: ['order_id'], name: 'IDX_order_id')]
#[ORM\Index(columns: ['delivery_time_min', 'delivery_time_max'], name: 'IDX_delivery_time')]
#[ORM\Index(columns: ['currency'], name: 'IDX_currency')]
class OrderArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Order $order = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $articleId = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $articleCode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $articleName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4)]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $priceEur = null;

    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    #[Assert\Length(exactly: 3)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Length(max: 5)]
    private ?string $measure = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveryTimeMin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveryTimeMax = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $weight = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $packagingCount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $pallet = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $packaging = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $swimmingPool = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function setArticleId(int $articleId): static
    {
        $this->articleId = $articleId;

        return $this;
    }

    public function getArticleCode(): ?string
    {
        return $this->articleCode;
    }

    public function setArticleCode(?string $articleCode): static
    {
        $this->articleCode = $articleCode;

        return $this;
    }

    public function getArticleName(): ?string
    {
        return $this->articleName;
    }

    public function setArticleName(?string $articleName): static
    {
        $this->articleName = $articleName;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceEur(): ?string
    {
        return $this->priceEur;
    }

    public function setPriceEur(?string $priceEur): static
    {
        $this->priceEur = $priceEur;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMeasure(): ?string
    {
        return $this->measure;
    }

    public function setMeasure(?string $measure): static
    {
        $this->measure = $measure;

        return $this;
    }

    public function getDeliveryTimeMin(): ?\DateTimeInterface
    {
        return $this->deliveryTimeMin;
    }

    public function setDeliveryTimeMin(?\DateTimeInterface $deliveryTimeMin): static
    {
        $this->deliveryTimeMin = $deliveryTimeMin;

        return $this;
    }

    public function getDeliveryTimeMax(): ?\DateTimeInterface
    {
        return $this->deliveryTimeMax;
    }

    public function setDeliveryTimeMax(?\DateTimeInterface $deliveryTimeMax): static
    {
        $this->deliveryTimeMax = $deliveryTimeMax;

        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getPackagingCount(): ?string
    {
        return $this->packagingCount;
    }

    public function setPackagingCount(?string $packagingCount): static
    {
        $this->packagingCount = $packagingCount;

        return $this;
    }

    public function getPallet(): ?string
    {
        return $this->pallet;
    }

    public function setPallet(?string $pallet): static
    {
        $this->pallet = $pallet;

        return $this;
    }

    public function getPackaging(): ?string
    {
        return $this->packaging;
    }

    public function setPackaging(?string $packaging): static
    {
        $this->packaging = $packaging;

        return $this;
    }

    public function isSwimmingPool(): bool
    {
        return $this->swimmingPool;
    }

    public function setSwimmingPool(bool $swimmingPool): static
    {
        $this->swimmingPool = $swimmingPool;

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

    public function getTotalPrice(): float
    {
        return floatval($this->price) * floatval($this->amount);
    }

    public function getTotalWeight(): ?float
    {
        if (!$this->weight) {
            return null;
        }
        return floatval($this->weight) * floatval($this->amount);
    }

    public function getDeliveryDays(): ?int
    {
        if (!$this->deliveryTimeMin || !$this->deliveryTimeMax) {
            return null;
        }

        $diff = $this->deliveryTimeMax->diff($this->deliveryTimeMin);
        return $diff->days;
    }
}
