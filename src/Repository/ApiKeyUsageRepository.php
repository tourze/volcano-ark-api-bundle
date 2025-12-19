<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;

/**
 * @extends ServiceEntityRepository<ApiKeyUsage>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: ApiKeyUsage::class)]
final class ApiKeyUsageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKeyUsage::class);
    }

    public function findOrCreateByKeyAndHour(ApiKey $apiKey, \DateTimeImmutable $hour, ?string $endpointId = null): ApiKeyUsage
    {
        $usage = $this->findOneBy([
            'apiKey' => $apiKey,
            'usageHour' => $hour,
            'endpointId' => $endpointId,
        ]);

        if (null === $usage) {
            $usage = new ApiKeyUsage();
            $usage->setApiKey($apiKey);
            $usage->setUsageHour($hour);
            $usage->setEndpointId($endpointId);

            $this->getEntityManager()->persist($usage);
        }

        return $usage;
    }

    /**
     * @return ApiKeyUsage[]
     */
    public function findByApiKeyAndDateRange(ApiKey $apiKey, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        /** @var ApiKeyUsage[] $result */
        $result = $this->createQueryBuilder('u')
            ->where('u.apiKey = :apiKey')
            ->andWhere('u.usageHour >= :startDate')
            ->andWhere('u.usageHour <= :endDate')
            ->setParameter('apiKey', $apiKey)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('u.usageHour', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * @return ApiKeyUsage[]
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        /** @var ApiKeyUsage[] $result */
        $result = $this->createQueryBuilder('u')
            ->where('u.usageHour >= :startDate')
            ->andWhere('u.usageHour <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('u.usageHour', 'ASC')
            ->addOrderBy('u.apiKey', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTotalUsageByApiKey(ApiKey $apiKey): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->createQueryBuilder('u')
            ->select('SUM(u.promptTokens) as totalPromptTokens')
            ->addSelect('SUM(u.completionTokens) as totalCompletionTokens')
            ->addSelect('SUM(u.totalTokens) as totalTokens')
            ->addSelect('SUM(u.requestCount) as totalRequests')
            ->addSelect('SUM(u.estimatedCost) as totalCost')
            ->where('u.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->getQuery()
            ->getSingleResult()
        ;

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHourlyUsageStats(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->createQueryBuilder('u')
            ->select('u.usageHour')
            ->addSelect('k.name as apiKeyName')
            ->addSelect('u.endpointId')
            ->addSelect('SUM(u.promptTokens) as promptTokens')
            ->addSelect('SUM(u.completionTokens) as completionTokens')
            ->addSelect('SUM(u.totalTokens) as totalTokens')
            ->addSelect('SUM(u.requestCount) as requestCount')
            ->leftJoin('u.apiKey', 'k')
            ->where('u.usageHour >= :startDate')
            ->andWhere('u.usageHour <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('u.usageHour')
            ->addGroupBy('k.id')
            ->addGroupBy('u.endpointId')
            ->orderBy('u.usageHour', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    public function getLastSyncedHour(): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('u')
            ->select('MAX(u.usageHour) as lastHour')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (is_string($result) && '' !== $result) ? new \DateTimeImmutable($result) : null;
    }

    public function save(ApiKeyUsage $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiKeyUsage $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
