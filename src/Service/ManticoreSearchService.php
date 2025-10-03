<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\OrderResponseDTO;
use App\Interface\SearchServiceInterface;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Psr\Log\LoggerInterface;

class ManticoreSearchService implements SearchServiceInterface
{
    private const INDEX_NAME = 'orders';
    private const CONNECTION_TIMEOUT = 5;

    public function __construct(
        private readonly string $manticoreHost,
        private readonly int $manticorePort,
        private readonly OrderRepository $orderRepository,
        private readonly OrderService $orderService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function searchOrders(string $query, int $page = 1, int $perPage = 20): array
    {
        try {
            $this->logger->info('Searching orders via Manticore', [
                'query' => $query,
                'page' => $page,
                'per_page' => $perPage
            ]);

            // Prepare search query with wildcard support
            $searchQuery = $this->prepareSearchQuery($query);

            // Connect to Manticore
            $connection = $this->createConnection();
            if (!$connection) {
                throw new \RuntimeException('Failed to connect to Manticore');
            }

            // Build SQL query
            $offset = ($page - 1) * $perPage;
            $sql = sprintf(
                "SELECT * FROM %s WHERE MATCH('%s') LIMIT %d, %d",
                self::INDEX_NAME,
                $searchQuery,
                $offset,
                $perPage
            );

            // Execute search
            $result = fwrite($connection, $sql . "\n");
            if ($result === false) {
                throw new \RuntimeException('Failed to send search query to Manticore');
            }

            // Read results
            $response = '';
            while (!feof($connection)) {
                $response .= fgets($connection);
            }
            fclose($connection);

            // Parse results
            $orders = $this->parseSearchResults($response);

            // Get total count
            $totalCount = $this->getTotalSearchCount($query);

            $this->logger->info('Search completed successfully', [
                'query' => $query,
                'results_count' => count($orders),
                'total_count' => $totalCount,
                'page' => $page
            ]);

            return [
                'orders' => $orders,
                'total' => $totalCount,
                'page' => $page,
                'perPage' => $perPage
            ];

        } catch (\Exception $e) {
            $this->logger->error('Search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback to database search
            return $this->fallbackDatabaseSearch($query, $page, $perPage);
        }
    }

    public function indexOrder(OrderResponseDTO $order): bool
    {
        try {
            $connection = $this->createConnection();
            if (!$connection) {
                throw new \RuntimeException('Failed to connect to Manticore');
            }

            // Prepare document for indexing
            $document = $this->prepareDocumentForIndexing($order);

            // Build INSERT query
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                self::INDEX_NAME,
                implode(', ', array_keys($document)),
                implode(', ', array_map(fn($value) => $this->escapeValue($value), $document))
            );

            // Execute insert
            $result = fwrite($connection, $sql . "\n");
            if ($result === false) {
                throw new \RuntimeException('Failed to send insert query to Manticore');
            }

            // Read response
            $response = '';
            while (!feof($connection)) {
                $response .= fgets($connection);
            }
            fclose($connection);

            $success = str_contains($response, 'Query OK');

            $this->logger->info('Order indexed in Manticore', [
                'order_id' => $order->id,
                'success' => $success,
                'response' => $response
            ]);

            return $success;

        } catch (\Exception $e) {
            $this->logger->error('Failed to index order in Manticore', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function removeOrder(int $orderId): bool
    {
        try {
            $connection = $this->createConnection();
            if (!$connection) {
                throw new \RuntimeException('Failed to connect to Manticore');
            }

            // Build DELETE query
            $sql = sprintf(
                "DELETE FROM %s WHERE id = %d",
                self::INDEX_NAME,
                $orderId
            );

            // Execute delete
            $result = fwrite($connection, $sql . "\n");
            if ($result === false) {
                throw new \RuntimeException('Failed to send delete query to Manticore');
            }

            // Read response
            $response = '';
            while (!feof($connection)) {
                $response .= fgets($connection);
            }
            fclose($connection);

            $success = str_contains($response, 'Query OK');

            $this->logger->info('Order removed from Manticore index', [
                'order_id' => $orderId,
                'success' => $success,
                'response' => $response
            ]);

            return $success;

        } catch (\Exception $e) {
            $this->logger->error('Failed to remove order from Manticore index', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function rebuildIndex(): bool
    {
        try {
            $this->logger->info('Starting Manticore index rebuild');

            // Get all orders from database
            $orders = $this->orderRepository->findAll();

            $successCount = 0;
            $totalCount = count($orders);

            foreach ($orders as $order) {
                $orderDTO = $this->orderService->entityToDto($order);
                if ($this->indexOrder($orderDTO)) {
                    $successCount++;
                }
            }

            $success = $successCount === $totalCount;

            $this->logger->info('Manticore index rebuild completed', [
                'total_orders' => $totalCount,
                'successfully_indexed' => $successCount,
                'success' => $success
            ]);

            return $success;

        } catch (\Exception $e) {
            $this->logger->error('Failed to rebuild Manticore index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    private function createConnection()
    {
        $connection = fsockopen($this->manticoreHost, $this->manticorePort, $errno, $errstr, self::CONNECTION_TIMEOUT);
        
        if (!$connection) {
            $this->logger->error('Failed to connect to Manticore', [
                'host' => $this->manticoreHost,
                'port' => $this->manticorePort,
                'error' => $errstr,
                'errno' => $errno
            ]);
            return false;
        }

        return $connection;
    }

    private function prepareSearchQuery(string $query): string
    {
        // Handle wildcard queries
        $query = trim($query);
        
        // If query doesn't contain wildcards, add them
        if (!str_contains($query, '*') && !str_contains($query, '?')) {
            $query = '*' . $query . '*';
        }

        // Escape special characters for Manticore
        $query = str_replace(['"', "'", '\\'], ['\\"', "\\'", '\\\\'], $query);

        return $query;
    }

    private function parseSearchResults(string $response): array
    {
        $orders = [];
        $lines = explode("\n", trim($response));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '+') || str_starts_with($line, '-') || str_starts_with($line, '|')) {
                continue;
            }

            // Parse result row (simplified - in real implementation you'd need proper parsing)
            $parts = explode(' ', $line);
            if (count($parts) >= 1 && is_numeric($parts[0])) {
                $orderId = (int) $parts[0];
                try {
                    $order = $this->orderService->getOrder($orderId);
                    $orders[] = $order;
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to load order from search result', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $orders;
    }

    private function getTotalSearchCount(string $query): int
    {
        try {
            $connection = $this->createConnection();
            if (!$connection) {
                return 0;
            }

            $searchQuery = $this->prepareSearchQuery($query);
            $sql = sprintf(
                "SELECT COUNT(*) FROM %s WHERE MATCH('%s')",
                self::INDEX_NAME,
                $searchQuery
            );

            $result = fwrite($connection, $sql . "\n");
            if ($result === false) {
                fclose($connection);
                return 0;
            }

            $response = '';
            while (!feof($connection)) {
                $response .= fgets($connection);
            }
            fclose($connection);

            // Parse count from response (simplified)
            if (preg_match('/\d+/', $response, $matches)) {
                return (int) $matches[0];
            }

            return 0;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get search count', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    private function fallbackDatabaseSearch(string $query, int $page, int $perPage): array
    {
        $this->logger->info('Using fallback database search', [
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage
        ]);

        $orders = $this->orderRepository->searchOrders($query, $page, $perPage);
        $totalCount = $this->orderRepository->getTotalSearchCount($query);

        $orderDTOs = [];
        foreach ($orders as $order) {
            $orderDTOs[] = $this->orderService->entityToDto($order);
        }

        return [
            'orders' => $orderDTOs,
            'total' => $totalCount,
            'page' => $page,
            'perPage' => $perPage
        ];
    }

    private function prepareDocumentForIndexing(OrderResponseDTO $order): array
    {
        return [
            'id' => $order->id,
            'client_name' => $order->clientName ?? '',
            'client_surname' => $order->clientSurname ?? '',
            'email' => $order->email ?? '',
            'company_name' => $order->companyName ?? '',
            'number' => $order->number ?? '',
            'articles' => implode(' ', array_column($order->articles, 'article_name')),
            'created_at' => $order->createdAt->getTimestamp(),
            'status' => $order->status,
            'currency' => $order->currency,
            'hash' => $order->hash
        ];
    }

    private function escapeValue($value): string
    {
        if (is_string($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string) $value;
    }
}
