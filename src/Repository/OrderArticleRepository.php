<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OrderArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderArticle>
 */
class OrderArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderArticle::class);
    }

    public function save(OrderArticle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderArticle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByArticleId(int $articleId): array
    {
        return $this->findBy(['articleId' => $articleId]);
    }

    public function findByArticleCode(string $articleCode): array
    {
        return $this->findBy(['articleCode' => $articleCode]);
    }

    public function findByOrderId(int $orderId): array
    {
        return $this->findBy(['order' => $orderId]);
    }

    public function findByDeliveryTimeRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('oa')
            ->andWhere('oa.deliveryTimeMin BETWEEN :from AND :to')
            ->orWhere('oa.deliveryTimeMax BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('oa.deliveryTimeMin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCurrency(string $currency): array
    {
        return $this->findBy(['currency' => $currency]);
    }

    public function findSwimmingPoolArticles(): array
    {
        return $this->findBy(['swimmingPool' => true]);
    }

    public function getTotalArticlesCount(): int
    {
        return $this->count([]);
    }

    public function getArticlesCountByOrderId(int $orderId): int
    {
        return $this->count(['order' => $orderId]);
    }

    public function getTotalWeightByOrderId(int $orderId): float
    {
        $result = $this->createQueryBuilder('oa')
            ->select('SUM(oa.weight * oa.amount) as totalWeight')
            ->andWhere('oa.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function getTotalPriceByOrderId(int $orderId): float
    {
        $result = $this->createQueryBuilder('oa')
            ->select('SUM(oa.price * oa.amount) as totalPrice')
            ->andWhere('oa.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function getArticlesByWeightRange(float $minWeight, float $maxWeight): array
    {
        return $this->createQueryBuilder('oa')
            ->andWhere('oa.weight BETWEEN :minWeight AND :maxWeight')
            ->setParameter('minWeight', $minWeight)
            ->setParameter('maxWeight', $maxWeight)
            ->orderBy('oa.weight', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getArticlesByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('oa')
            ->andWhere('oa.price BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('oa.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findArticlesByName(string $name): array
    {
        return $this->createQueryBuilder('oa')
            ->andWhere('oa.articleName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('oa.articleName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMostPopularArticles(int $limit = 10): array
    {
        return $this->createQueryBuilder('oa')
            ->select('oa.articleId, oa.articleName, oa.articleCode, SUM(oa.amount) as totalAmount')
            ->groupBy('oa.articleId, oa.articleName, oa.articleCode')
            ->orderBy('totalAmount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getArticlesByMeasure(string $measure): array
    {
        return $this->findBy(['measure' => $measure]);
    }

    public function getRecentArticles(int $limit = 20): array
    {
        return $this->createQueryBuilder('oa')
            ->orderBy('oa.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
