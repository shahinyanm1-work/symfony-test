<?php

declare(strict_types=1);

namespace App\Controller;

use App\Interface\SearchServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/orders', name: 'api_orders_')]
class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchServiceInterface $searchService,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[OA\Get(
        path: '/api/orders/search',
        summary: 'Search orders',
        description: 'Search orders with wildcard support using Manticore Search',
        tags: ['Orders', 'Search'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query with wildcard support (e.g., "john*", "*smith", "*test*")',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'john*')
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(ref: '#/components/schemas/SearchResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid query parameters',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 500,
                description: 'Search service error',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function searchOrders(Request $request): JsonResponse
    {
        try {
            // Get and validate parameters
            $query = $request->query->get('q', '');
            $page = max(1, (int) $request->query->get('page', 1));
            $perPage = max(1, min(100, (int) $request->query->get('per_page', 20)));

            // Validate query parameter
            if (empty($query)) {
                return new JsonResponse([
                    'error' => 'Missing required parameter',
                    'message' => 'Query parameter "q" is required'
                ], 400);
            }

            // Validate query length
            if (strlen($query) < 2) {
                return new JsonResponse([
                    'error' => 'Invalid query',
                    'message' => 'Query must be at least 2 characters long'
                ], 400);
            }

            // Validate query format (basic check for dangerous characters)
            $violations = $this->validator->validate($query, [
                new Assert\NotBlank(),
                new Assert\Length(min: 2, max: 200),
                new Assert\Regex(pattern: '/^[a-zA-Z0-9\s\*\.\-\_]+$/', message: 'Query contains invalid characters')
            ]);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }

                return new JsonResponse([
                    'error' => 'Invalid query',
                    'details' => $errors
                ], 400);
            }

            $this->logger->info('Search request received', [
                'query' => $query,
                'page' => $page,
                'per_page' => $perPage
            ]);

            // Perform search
            $searchResults = $this->searchService->searchOrders($query, $page, $perPage);

            // Format response
            $response = [
                'meta' => [
                    'query' => $query,
                    'page' => $searchResults['page'],
                    'per_page' => $searchResults['perPage'],
                    'total' => $searchResults['total'],
                    'total_pages' => (int) ceil($searchResults['total'] / $searchResults['perPage'])
                ],
                'data' => array_map(fn($order) => $order->toArray(), $searchResults['orders'])
            ];

            $this->logger->info('Search completed successfully', [
                'query' => $query,
                'results_count' => count($searchResults['orders']),
                'total_count' => $searchResults['total'],
                'page' => $page
            ]);

            return new JsonResponse($response);

        } catch (\Exception $e) {
            $this->logger->error('Search error', [
                'query' => $request->query->get('q'),
                'page' => $request->query->get('page'),
                'per_page' => $request->query->get('per_page'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Search service error',
                'message' => 'An error occurred while performing the search'
            ], 500);
        }
    }
}
