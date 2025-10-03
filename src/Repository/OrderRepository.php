<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByIdOrHash(int|string $identifier): ?Order
    {
        $qb = $this->createQueryBuilder('o');

        if (is_numeric($identifier)) {
            return $qb
                ->andWhere('o.id = :identifier')
                ->setParameter('identifier', (int) $identifier)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $qb
            ->andWhere('o.hash = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUuid(string $uuid): ?Order
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findByHash(string $hash): ?Order
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    public function findByNumber(string $number): ?Order
    {
        return $this->findOneBy(['number' => $number]);
    }

    public function findByEmail(string $email): array
    {
        return $this->findBy(['email' => $email]);
    }

    public function findByStatus(int $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(int $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    public function getAggregatedOrders(
        string $groupBy,
        int $page = 1,
        int $perPage = 20,
        ?int $status = null,
        ?\DateTimeInterface $fromDate = null,
        ?\DateTimeInterface $toDate = null,
        ?int $userId = null
    ): array {
        $conn = $this->getEntityManager()->getConnection();

        switch ($groupBy) {
            case 'day':
                $groupExpr = 'DATE(o.created_at)';
                break;
            case 'month':
                $groupExpr = "DATE_FORMAT(o.created_at, '%Y-%m')";
                break;
            case 'year':
                $groupExpr = 'YEAR(o.created_at)';
                break;
            default:
                throw new \InvalidArgumentException("Invalid group_by parameter. Must be 'day', 'month', or 'year'.");
        }

        $where = [];
        $params = [];

        if ($status !== null) {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }
        if ($fromDate !== null) {
            $where[] = 'o.created_at >= :fromDate';
            $params['fromDate'] = $fromDate->format('Y-m-d H:i:s');
        }
        if ($toDate !== null) {
            $where[] = 'o.created_at <= :toDate';
            $params['toDate'] = $toDate->format('Y-m-d H:i:s');
        }
        if ($userId !== null) {
            $where[] = 'o.user_id = :userId';
            $params['userId'] = $userId;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT {$groupExpr} AS `group`, COUNT(o.id) AS `count`
                FROM orders o
                {$whereSql}
                GROUP BY {$groupExpr}
                ORDER BY `group` DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        // Принудительно как integers без кавычек
        $stmt->bindValue('limit', $perPage, \Doctrine\DBAL\ParameterType::INTEGER);
        $stmt->bindValue('offset', $offset, \Doctrine\DBAL\ParameterType::INTEGER);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getTotalAggregatedCount(
        string $groupBy,
        ?int $status = null,
        ?\DateTimeInterface $fromDate = null,
        ?\DateTimeInterface $toDate = null,
        ?int $userId = null
    ): int {
        $conn = $this->getEntityManager()->getConnection();

        switch ($groupBy) {
            case 'day':
                $groupExpr = 'DATE(o.created_at)';
                break;
            case 'month':
                $groupExpr = "DATE_FORMAT(o.created_at, '%Y-%m')";
                break;
            case 'year':
                $groupExpr = 'YEAR(o.created_at)';
                break;
            default:
                throw new \InvalidArgumentException("Invalid group_by parameter. Must be 'day', 'month', or 'year'.");
        }

        $where = [];
        $params = [];
        if ($status !== null) { $where[] = 'o.status = :status'; $params['status'] = $status; }
        if ($fromDate !== null) { $where[] = 'o.created_at >= :fromDate'; $params['fromDate'] = $fromDate->format('Y-m-d H:i:s'); }
        if ($toDate !== null) { $where[] = 'o.created_at <= :toDate'; $params['toDate'] = $toDate->format('Y-m-d H:i:s'); }
        if ($userId !== null) { $where[] = 'o.user_id = :userId'; $params['userId'] = $userId; }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT COUNT(*) AS c FROM (
                    SELECT {$groupExpr}
                    FROM orders o
                    {$whereSql}
                    GROUP BY {$groupExpr}
                ) t";

        $stmt = $conn->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        return (int) $stmt->executeQuery()->fetchOne();
    }

    public function searchOrders(string $query, int $page = 1, int $perPage = 20): array
    {
        $qb = $this->createQueryBuilder('o');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('o.clientName', ':query'),
                $qb->expr()->like('o.clientSurname', ':query'),
                $qb->expr()->like('o.email', ':query'),
                $qb->expr()->like('o.companyName', ':query'),
                $qb->expr()->like('o.number', ':query'),
                $qb->expr()->like('o.hash', ':query')
            )
        )
        ->setParameter('query', '%' . $query . '%')
        ->orderBy('o.createdAt', 'DESC');

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $qb->setFirstResult($offset)
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }

    public function getTotalSearchCount(string $query): int
    {
        $qb = $this->createQueryBuilder('o');

        $qb->select('COUNT(o.id)')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('o.clientName', ':query'),
                    $qb->expr()->like('o.clientSurname', ':query'),
                    $qb->expr()->like('o.email', ':query'),
                    $qb->expr()->like('o.companyName', ':query'),
                    $qb->expr()->like('o.number', ':query'),
                    $qb->expr()->like('o.hash', ':query')
                )
            )
            ->setParameter('query', '%' . $query . '%');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getOrdersByStatus(int $status, int $limit = 100): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalOrdersCount(): int
    {
        return $this->count([]);
    }

    public function getOrdersCountByStatus(int $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function getOrdersCountByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
