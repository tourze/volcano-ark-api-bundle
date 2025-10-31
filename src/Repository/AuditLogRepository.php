<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\VolcanoArkApiBundle\Entity\AuditLog;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: AuditLog::class)]
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * 查找指定 API 密钥的审计日志
     * @return AuditLog[]
     */
    public function findByApiKey(int $apiKeyId, int $limit = 50): array
    {
        /** @var AuditLog[] $result */
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.apiKey = :apiKeyId')
            ->setParameter('apiKeyId', $apiKeyId)
            ->orderBy('a.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * 查找指定时间范围内的审计日志
     * @return AuditLog[]
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        /** @var AuditLog[] $result */
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.createTime >= :startDate')
            ->andWhere('a.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * 查找失败的审计日志
     * @return AuditLog[]
     */
    public function findFailedLogs(int $limit = 50): array
    {
        /** @var AuditLog[] $result */
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.isSuccess = false')
            ->orderBy('a.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * 统计指定 API 密钥的使用次数
     */
    public function countByApiKey(int $apiKeyId): int
    {
        $result = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.apiKey = :apiKeyId')
            ->setParameter('apiKeyId', $apiKeyId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 清理过期的审计日志
     */
    public function removeOldLogs(\DateTimeImmutable $cutoffDate): int
    {
        $qb = $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.createTime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
        ;

        $result = $qb->getQuery()->execute();
        assert(is_int($result));

        return $result;
    }

    public function save(AuditLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuditLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
