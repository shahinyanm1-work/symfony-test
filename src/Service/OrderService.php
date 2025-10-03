<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\OrderDTO;
use App\DTO\OrderResponseDTO;
use App\Entity\Order;
use App\Entity\OrderArticle;
use App\Exception\OrderCreationException;
use App\Exception\OrderNotFoundException;
use App\Exception\OrderValidationException;
use App\Interface\OrderServiceInterface;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createOrder(OrderDTO $orderDTO): OrderResponseDTO
    {
        // Validate DTO
        $violations = $this->validator->validate($orderDTO);
        if (count($violations) > 0) {
            throw new OrderValidationException('Order validation failed: ' . (string) $violations);
        }

        try {
            // Create order entity
            $order = new Order();
            $order->setUuid((string) Uuid::v4());
            $order->setHash(hash('sha256', 'order_' . time() . '_' . uniqid()));
            $order->setToken(substr(hash('md5', uniqid()), 0, 32));
            $order->setName($orderDTO->name);
            $order->setClientName($orderDTO->clientName);
            $order->setClientSurname($orderDTO->clientSurname);
            $order->setEmail($orderDTO->email);
            $order->setCompanyName($orderDTO->companyName);
            $order->setDescription($orderDTO->description);
            $order->setCurrency($orderDTO->currency);
            $order->setDiscount($orderDTO->discount);
            $order->setDeliveryPrice($orderDTO->deliveryPrice ? (string) $orderDTO->deliveryPrice : null);
            $order->setDeliveryType($orderDTO->deliveryType);
            $order->setDeliveryIndex($orderDTO->deliveryIndex);
            $order->setDeliveryRegion($orderDTO->deliveryRegion);
            $order->setDeliveryCity($orderDTO->deliveryCity);
            $order->setDeliveryAddress($orderDTO->deliveryAddress);
            $order->setDeliveryPhone($orderDTO->deliveryPhone);
            $order->setPayType($orderDTO->payType);
            $order->setLocale($orderDTO->locale);
            $order->setMeasure($orderDTO->measure);

            // Generate order number
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad((string) ($this->orderRepository->getTotalOrdersCount() + 1), 3, '0', STR_PAD_LEFT);
            $order->setNumber($orderNumber);

            // Create order articles
            foreach ($orderDTO->articles as $articleDTO) {
                $article = new OrderArticle();
                $article->setOrder($order);
                $article->setArticleId($articleDTO->articleId);
                $article->setArticleCode($articleDTO->articleCode);
                $article->setArticleName($articleDTO->articleName);
                $article->setAmount((string) $articleDTO->amount);
                $article->setPrice((string) $articleDTO->price);
                $article->setPriceEur($articleDTO->priceEur ? (string) $articleDTO->priceEur : null);
                $article->setCurrency($articleDTO->currency);
                $article->setMeasure($articleDTO->measure);
                $article->setDeliveryTimeMin($articleDTO->deliveryTimeMin);
                $article->setDeliveryTimeMax($articleDTO->deliveryTimeMax);
                $article->setWeight($articleDTO->weight ? (string) $articleDTO->weight : null);
                $article->setPackagingCount($articleDTO->packagingCount ? (string) $articleDTO->packagingCount : null);
                $article->setPallet($articleDTO->pallet ? (string) $articleDTO->pallet : null);
                $article->setPackaging($articleDTO->packaging ? (string) $articleDTO->packaging : null);
                $article->setSwimmingPool($articleDTO->swimmingPool);

                $order->addArticle($article);
            }

            // Save to database
            $this->orderRepository->save($order, true);

            return $this->entityToDto($order);

        } catch (\Exception $e) {
            throw new OrderCreationException('Failed to create order: ' . $e->getMessage(), $e);
        }
    }

    public function getOrder(int|string $identifier): OrderResponseDTO
    {
        $order = $this->orderRepository->findByIdOrHash($identifier);

        if (!$order) {
            throw new OrderNotFoundException('Order not found');
        }

        return $this->entityToDto($order);
    }

    public function updateOrderStatus(int $orderId, int $status): OrderResponseDTO
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new OrderNotFoundException('Order not found');
        }

        // Validate status
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        if (!in_array($status, $validStatuses)) {
            throw new OrderValidationException('Invalid order status');
        }

        $order->setStatus($status);
        $order->setUpdatedAt(new \DateTimeImmutable());

        $this->orderRepository->save($order, true);

        return $this->entityToDto($order);
    }

    public function entityToDto(Order $order): OrderResponseDTO
    {
        $articles = [];
        foreach ($order->getArticles() as $article) {
            $articles[] = [
                'id' => $article->getId(),
                'article_id' => $article->getArticleId(),
                'article_code' => $article->getArticleCode(),
                'article_name' => $article->getArticleName(),
                'amount' => $article->getAmount(),
                'price' => $article->getPrice(),
                'price_eur' => $article->getPriceEur(),
                'currency' => $article->getCurrency(),
                'measure' => $article->getMeasure(),
                'delivery_time_min' => $article->getDeliveryTimeMin()?->format('Y-m-d'),
                'delivery_time_max' => $article->getDeliveryTimeMax()?->format('Y-m-d'),
                'weight' => $article->getWeight(),
                'packaging_count' => $article->getPackagingCount(),
                'pallet' => $article->getPallet(),
                'packaging' => $article->getPackaging(),
                'swimming_pool' => $article->isSwimmingPool(),
                'created_at' => $article->getCreatedAt()?->format('c'),
                'updated_at' => $article->getUpdatedAt()?->format('c'),
            ];
        }

        return new OrderResponseDTO(
            id: $order->getId(),
            uuid: $order->getUuid(),
            hash: $order->getHash(),
            userId: $order->getUserId(),
            token: $order->getToken(),
            number: $order->getNumber(),
            status: $order->getStatus(),
            email: $order->getEmail(),
            vatType: $order->getVatType(),
            vatNumber: $order->getVatNumber(),
            discount: $order->getDiscount(),
            deliveryPrice: $order->getDeliveryPrice(),
            deliveryType: $order->getDeliveryType(),
            deliveryIndex: $order->getDeliveryIndex(),
            deliveryCountry: $order->getDeliveryCountry(),
            deliveryRegion: $order->getDeliveryRegion(),
            deliveryCity: $order->getDeliveryCity(),
            deliveryAddress: $order->getDeliveryAddress(),
            deliveryPhone: $order->getDeliveryPhone(),
            clientName: $order->getClientName(),
            clientSurname: $order->getClientSurname(),
            companyName: $order->getCompanyName(),
            payType: $order->getPayType(),
            payDateExecution: $order->getPayDateExecution(),
            proposedDate: $order->getProposedDate(),
            shipDate: $order->getShipDate(),
            trackingNumber: $order->getTrackingNumber(),
            managerName: $order->getManagerName(),
            managerEmail: $order->getManagerEmail(),
            locale: $order->getLocale(),
            curRate: $order->getCurRate(),
            currency: $order->getCurrency(),
            measure: $order->getMeasure(),
            name: $order->getName(),
            description: $order->getDescription(),
            createdAt: $order->getCreatedAt(),
            updatedAt: $order->getUpdatedAt(),
            warehouseData: $order->getWarehouseData(),
            addressEqual: $order->isAddressEqual(),
            acceptPay: $order->isAcceptPay(),
            weightGross: $order->getWeightGross(),
            paymentEuro: $order->isPaymentEuro(),
            specPrice: $order->isSpecPrice(),
            deliveryApartmentOffice: $order->getDeliveryApartmentOffice(),
            articles: $articles
        );
    }
}
