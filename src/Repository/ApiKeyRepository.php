<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: ApiKey::class)]
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findActiveKey(): ?ApiKey
    {
        $result = $this->createQueryBuilder('k')
            ->where('k.isActive = :active')
            ->andWhere('k.provider = :provider')
            ->setParameter('active', true)
            ->setParameter('provider', 'volcano_ark')
            ->orderBy('k.usageCount', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof ApiKey || null === $result);

        return $result;
    }

    /**
     * @return ApiKey[]
     */
    public function findActiveKeys(): array
    {
        /** @var ApiKey[] $result */
        $result = $this->createQueryBuilder('k')
            ->where('k.isActive = :active')
            ->andWhere('k.provider = :provider')
            ->setParameter('active', true)
            ->setParameter('provider', 'volcano_ark')
            ->orderBy('k.usageCount', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    public function findByName(string $name): ?ApiKey
    {
        $result = $this->createQueryBuilder('k')
            ->where('k.name = :name')
            ->andWhere('k.provider = :provider')
            ->setParameter('name', $name)
            ->setParameter('provider', 'volcano_ark')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof ApiKey || null === $result);

        return $result;
    }

    public function save(ApiKey $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiKey $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ApiKey[]
     */
    public function findActiveAndValidKeys(): array
    {
        /** @var ApiKey[] $result */
        $result = $this->createQueryBuilder('k')
            ->where('k.isActive = :active')
            ->andWhere('k.provider = :provider')
            ->setParameter('active', true)
            ->setParameter('provider', 'volcano_ark')
            ->orderBy('k.usageCount', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * @return ApiKey[]
     */
    public function findByPriority(): array
    {
        /** @var ApiKey[] $result */
        $result = $this->createQueryBuilder('k')
            ->where('k.isActive = :active')
            ->andWhere('k.provider = :provider')
            ->setParameter('active', true)
            ->setParameter('provider', 'volcano_ark')
            ->orderBy('k.usageCount', 'ASC')
            ->addOrderBy('k.lastUsedTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }
}
