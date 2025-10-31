<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;

/**
 * 自动更新时间戳的实体监听器
 */
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: ApiKey::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: ApiKeyUsage::class)]
class TimestampableListener
{
    public function preUpdate(object $entity, PreUpdateEventArgs $args): void
    {
        if ($entity instanceof ApiKey || $entity instanceof ApiKeyUsage) {
            $entity->setUpdateTime(new \DateTimeImmutable());
        }
    }
}
