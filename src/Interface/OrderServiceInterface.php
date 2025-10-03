<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\OrderDTO;
use App\DTO\OrderResponseDTO;
use App\Entity\Order;

interface OrderServiceInterface
{
    /**
     * Create a new order from DTO
     *
     * @param OrderDTO $orderDTO Order data
     * @return OrderResponseDTO Created order
     * @throws \App\Exception\OrderValidationException When validation fails
     * @throws \App\Exception\OrderCreationException When creation fails
     */
    public function createOrder(OrderDTO $orderDTO): OrderResponseDTO;

    /**
     * Get order by ID or hash
     *
     * @param int|string $identifier Order ID or hash
     * @return OrderResponseDTO Order data
     * @throws \App\Exception\OrderNotFoundException When order is not found
     */
    public function getOrder(int|string $identifier): OrderResponseDTO;

    /**
     * Update order status
     *
     * @param int $orderId Order ID
     * @param int $status New status
     * @return OrderResponseDTO Updated order
     * @throws \App\Exception\OrderNotFoundException When order is not found
     * @throws \App\Exception\OrderValidationException When validation fails
     */
    public function updateOrderStatus(int $orderId, int $status): OrderResponseDTO;

    /**
     * Convert Entity to DTO
     *
     * @param Order $order Order entity
     * @return OrderResponseDTO Order DTO
     */
    public function entityToDto(Order $order): OrderResponseDTO;
}
