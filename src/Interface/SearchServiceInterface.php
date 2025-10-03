<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\OrderResponseDTO;

interface SearchServiceInterface
{
    /**
     * Search orders using wildcard queries
     *
     * @param string $query Search query with wildcard support
     * @param int $page Page number
     * @param int $perPage Results per page
     * @return array{orders: OrderResponseDTO[], total: int, page: int, perPage: int}
     */
    public function searchOrders(string $query, int $page = 1, int $perPage = 20): array;

    /**
     * Index an order in the search engine
     *
     * @param OrderResponseDTO $order Order to index
     * @return bool Success status
     */
    public function indexOrder(OrderResponseDTO $order): bool;

    /**
     * Remove an order from the search index
     *
     * @param int $orderId Order ID to remove
     * @return bool Success status
     */
    public function removeOrder(int $orderId): bool;

    /**
     * Rebuild the entire search index
     *
     * @return bool Success status
     */
    public function rebuildIndex(): bool;
}
