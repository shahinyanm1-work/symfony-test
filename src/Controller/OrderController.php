<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AggregationResponseDTO;
use App\DTO\OrderResponseDTO;
use App\Exception\OrderNotFoundException;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderRepository $orderRepository,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/aggregate', name: 'aggregate', methods: ['GET'])]
    #[OA\Get(
        path: '/api/orders/aggregate',
        summary: 'Aggregate orders',
        description: 'Get aggregated order statistics with grouping by date and pagination',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'group_by',
                description: 'Grouping period',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['day', 'month', 'year'], example: 'month')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Filter by order status',
                in: 'query',
                schema: new OA\Schema(type: 'integer', enum: [1, 2, 3, 4, 5])
            ),
            new OA\Parameter(
                name: 'from_date',
                description: 'Start date filter (Y-m-d)',
                in: 'query',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to_date',
                description: 'End date filter (Y-m-d)',
                in: 'query',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'user_id',
                description: 'Filter by user ID',
                in: 'query',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregated order data',
                content: new OA\JsonContent(ref: '#/components/schemas/AggregationResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid parameters',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function aggregateOrders(Request $request): JsonResponse
    {
        try {
            // Get and validate parameters
            $page = max(1, (int) $request->query->get('page', 1));
            $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));
            $groupBy = $request->query->get('group_by');
            $status = $request->query->get('status') ? (int) $request->query->get('status') : null;
            $fromDate = $request->query->get('from_date') ? new \DateTimeImmutable($request->query->get('from_date')) : null;
            $toDate = $request->query->get('to_date') ? new \DateTimeImmutable($request->query->get('to_date')) : null;
            $userId = $request->query->get('user_id') ? (int) $request->query->get('user_id') : null;

            // Validate group_by parameter
            if (!$groupBy || !in_array($groupBy, ['day', 'month', 'year'])) {
                return new JsonResponse([
                    'error' => 'Invalid or missing group_by parameter',
                    'message' => 'group_by must be one of: day, month, year'
                ], 400);
            }

            // Validate date range
            if ($fromDate && $toDate && $fromDate > $toDate) {
                return new JsonResponse([
                    'error' => 'Invalid date range',
                    'message' => 'from_date must be before or equal to to_date'
                ], 400);
            }

            // Validate status if provided
            if ($status !== null) {
                $validStatuses = [1, 2, 3, 4, 5]; // Order status constants
                if (!in_array($status, $validStatuses)) {
                    return new JsonResponse([
                        'error' => 'Invalid status',
                        'message' => 'status must be one of: 1, 2, 3, 4, 5'
                    ], 400);
                }
            }

            // Get aggregated data
            $aggregatedData = $this->orderRepository->getAggregatedOrders(
                $groupBy,
                $page,
                $perPage,
                $status,
                $fromDate,
                $toDate,
                $userId
            );

            // Get total count for pagination
            $totalItems = $this->orderRepository->getTotalAggregatedCount(
                $groupBy,
                $status,
                $fromDate,
                $toDate,
                $userId
            );

            $totalPages = (int) ceil($totalItems / $perPage);

            // Format response data
            $formattedData = array_map(function ($item) {
                return [
                    'group' => (string) $item['group'],
                    'count' => (int) $item['count']
                ];
            }, $aggregatedData);

            $response = new AggregationResponseDTO(
                data: $formattedData,
                page: $page,
                perPage: $perPage,
                totalPages: $totalPages,
                totalItems: $totalItems
            );

            $this->logger->info('Orders aggregated successfully', [
                'group_by' => $groupBy,
                'page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'filters' => [
                    'status' => $status,
                    'from_date' => $fromDate?->format('Y-m-d'),
                    'to_date' => $toDate?->format('Y-m-d'),
                    'user_id' => $userId
                ]
            ]);

            return new JsonResponse($response->toArray());

        } catch (\Exception $e) {
            $this->logger->error('Error in orders aggregation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->query->all()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred while aggregating orders'
            ], 500);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/orders/{id}',
        summary: 'Get order by ID',
        description: 'Retrieve order details by ID',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Order ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order details',
                content: new OA\JsonContent(ref: '#/components/schemas/OrderResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Order not found',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getOrder(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($id);

            $this->logger->info('Order retrieved successfully', [
                'order_id' => $id
            ]);

            return new JsonResponse($order->toArray());

        } catch (OrderNotFoundException $e) {
            $this->logger->warning('Order not found', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'error' => 'Order not found',
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving order', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred while retrieving the order'
            ], 500);
        }
    }

    #[Route('/{hash}', name: 'get_by_hash', methods: ['GET'], requirements: ['hash' => '[a-zA-Z0-9]+'])]
    public function getOrderByHash(string $hash, Request $request): JsonResponse
    {
        try {
            $by = $request->query->get('by', 'hash');
            
            if ($by === 'hash') {
                $order = $this->orderService->getOrder($hash);
            } else {
                return new JsonResponse([
                    'error' => 'Invalid parameter',
                    'message' => 'by parameter must be "hash"'
                ], 400);
            }

            $this->logger->info('Order retrieved by hash successfully', [
                'hash' => $hash,
                'by' => $by
            ]);

            return new JsonResponse($order->toArray());

        } catch (OrderNotFoundException $e) {
            $this->logger->warning('Order not found by hash', [
                'hash' => $hash,
                'by' => $request->query->get('by'),
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'error' => 'Order not found',
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving order by hash', [
                'hash' => $hash,
                'by' => $request->query->get('by'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred while retrieving the order'
            ], 500);
        }
    }
}
