<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\PriceFetcherException;
use App\Exception\PriceNotFoundException;
use App\Service\PriceFetcherService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class PriceController extends AbstractController
{
    public function __construct(
        private readonly PriceFetcherService $priceFetcherService,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/price', name: 'price', methods: ['GET'])]
    public function getPrice(Request $request): JsonResponse
    {
        try {
            // Validate required parameters
            $factory = $request->query->get('factory');
            $collection = $request->query->get('collection');
            $article = $request->query->get('article');

            if (!$factory || !$collection || !$article) {
                return new JsonResponse([
                    'error' => 'Missing required parameters: factory, collection, article'
                ], 400);
            }

            // Validate parameter format
            $violations = $this->validator->validate([
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article
            ], [
                'factory' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
                'collection' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
                'article' => [new Assert\NotBlank(), new Assert\Length(max: 200)]
            ]);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                }

                return new JsonResponse([
                    'error' => 'Invalid parameters',
                    'details' => $errors
                ], 400);
            }

            // Fetch price
            $priceData = $this->priceFetcherService->fetchPrice($factory, $collection, $article);

            $this->logger->info('Price successfully fetched via API', [
                'factory' => $factory,
                'collection' => $collection,
                'article' => $article,
                'price' => $priceData->price,
                'currency' => $priceData->currency
            ]);

            return new JsonResponse($priceData->toArray());

        } catch (PriceNotFoundException $e) {
            $this->logger->warning('Price not found via API', [
                'factory' => $request->query->get('factory'),
                'collection' => $request->query->get('collection'),
                'article' => $request->query->get('article'),
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'error' => 'Price not found',
                'message' => $e->getMessage()
            ], 404);

        } catch (PriceFetcherException $e) {
            $this->logger->error('Price fetcher service error via API', [
                'factory' => $request->query->get('factory'),
                'collection' => $request->query->get('collection'),
                'article' => $request->query->get('article'),
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'error' => 'Price service unavailable',
                'message' => $e->getMessage()
            ], 503);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in price API', [
                'factory' => $request->query->get('factory'),
                'collection' => $request->query->get('collection'),
                'article' => $request->query->get('article'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }
}
