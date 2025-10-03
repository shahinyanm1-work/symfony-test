<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\OrderArticleDTO;
use App\DTO\OrderDTO;
use App\Entity\Order;
use App\Entity\OrderArticle;
use App\Exception\OrderCreationException;
use App\Exception\OrderNotFoundException;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private EntityManagerInterface $mockEntityManager;
    private OrderRepository $mockOrderRepository;
    private ValidatorInterface $mockValidator;

    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockOrderRepository = $this->createMock(OrderRepository::class);
        $this->mockValidator = $this->createMock(ValidatorInterface::class);

        $this->orderService = new OrderService(
            $this->mockEntityManager,
            $this->mockOrderRepository,
            $this->mockValidator
        );
    }

    public function testCreateOrderSuccess(): void
    {
        $orderDTO = $this->createValidOrderDTO();

        // Mock validation success
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new \Symfony\Component\Validator\ConstraintViolationList());

        // Mock repository save
        $this->mockOrderRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Order $order) {
                $order->setId(1);
                $order->setUuid('test-uuid');
                $order->setHash('test-hash');
                $order->setToken('test-token');
                return true;
            });

        $this->mockOrderRepository->expects($this->once())
            ->method('getTotalOrdersCount')
            ->willReturn(0);

        $result = $this->orderService->createOrder($orderDTO);

        $this->assertEquals(1, $result->id);
        $this->assertEquals('test-uuid', $result->uuid);
        $this->assertEquals('test-hash', $result->hash);
        $this->assertEquals('ORD-2025-001', $result->number);
        $this->assertEquals('John', $result->clientName);
        $this->assertEquals('Doe', $result->clientSurname);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function testCreateOrderValidationFailure(): void
    {
        $orderDTO = $this->createValidOrderDTO();

        // Mock validation failure
        $violations = new \Symfony\Component\Validator\ConstraintViolationList();
        $violations->add(new \Symfony\Component\Validator\ConstraintViolation(
            'Invalid email',
            'Invalid email',
            [],
            $orderDTO,
            'email',
            'invalid-email'
        ));

        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(\App\Exception\OrderValidationException::class);
        $this->expectExceptionMessage('Order validation failed');

        $this->orderService->createOrder($orderDTO);
    }

    public function testGetOrderById(): void
    {
        $order = $this->createMockOrder();
        $order->setId(1);

        $this->mockOrderRepository->expects($this->once())
            ->method('findByIdOrHash')
            ->with(1)
            ->willReturn($order);

        $result = $this->orderService->getOrder(1);

        $this->assertEquals(1, $result->id);
        $this->assertEquals('test-uuid', $result->uuid);
        $this->assertEquals('test-hash', $result->hash);
    }

    public function testGetOrderByHash(): void
    {
        $order = $this->createMockOrder();
        $order->setHash('test-hash');

        $this->mockOrderRepository->expects($this->once())
            ->method('findByIdOrHash')
            ->with('test-hash')
            ->willReturn($order);

        $result = $this->orderService->getOrder('test-hash');

        $this->assertEquals('test-hash', $result->hash);
    }

    public function testGetOrderNotFound(): void
    {
        $this->mockOrderRepository->expects($this->once())
            ->method('findByIdOrHash')
            ->with(999)
            ->willReturn(null);

        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage('Order not found');

        $this->orderService->getOrder(999);
    }

    public function testUpdateOrderStatusSuccess(): void
    {
        $order = $this->createMockOrder();
        $order->setId(1);
        $order->setStatus(1); // PENDING

        $this->mockOrderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->mockOrderRepository->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $result = $this->orderService->updateOrderStatus(1, 2); // CONFIRMED

        $this->assertEquals(2, $result->status);
    }

    public function testUpdateOrderStatusInvalidStatus(): void
    {
        $order = $this->createMockOrder();
        $order->setId(1);

        $this->mockOrderRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($order);

        $this->expectException(\App\Exception\OrderValidationException::class);
        $this->expectExceptionMessage('Invalid order status');

        $this->orderService->updateOrderStatus(1, 999);
    }

    public function testUpdateOrderStatusNotFound(): void
    {
        $this->mockOrderRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage('Order not found');

        $this->orderService->updateOrderStatus(999, 2);
    }

    private function createValidOrderDTO(): OrderDTO
    {
        $orderDTO = new OrderDTO();
        $orderDTO->clientName = 'John';
        $orderDTO->clientSurname = 'Doe';
        $orderDTO->email = 'john@example.com';
        $orderDTO->name = 'Test Order';
        $orderDTO->currency = 'EUR';

        $articleDTO = new OrderArticleDTO();
        $articleDTO->articleId = 1001;
        $articleDTO->articleCode = 'TILE-001';
        $articleDTO->articleName = 'Test Tile';
        $articleDTO->amount = 10.0;
        $articleDTO->price = 25.99;
        $articleDTO->currency = 'EUR';

        $orderDTO->articles = [$articleDTO];

        return $orderDTO;
    }

    private function createMockOrder(): Order
    {
        $order = new Order();
        $order->setUuid('test-uuid');
        $order->setHash('test-hash');
        $order->setToken('test-token');
        $order->setName('Test Order');
        $order->setClientName('John');
        $order->setClientSurname('Doe');
        $order->setEmail('john@example.com');
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setUpdatedAt(new \DateTimeImmutable());

        return $order;
    }
}
