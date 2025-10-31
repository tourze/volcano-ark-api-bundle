<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\ApiKeyUsage;

class ApiKeyUsageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 使用引用获取 API 密钥
        $apiKey1 = $this->getReference(ApiKeyFixtures::API_KEY_1_REFERENCE, ApiKey::class);
        $apiKey2 = $this->getReference(ApiKeyFixtures::API_KEY_2_REFERENCE, ApiKey::class);

        if ($apiKey1 instanceof ApiKey) {
            $this->createUsageRecords($manager, $apiKey1);
        }

        if ($apiKey2 instanceof ApiKey) {
            $this->createUsageRecords($manager, $apiKey2);
        }

        $manager->flush();
    }

    private function createUsageRecords(ObjectManager $manager, ApiKey $apiKey): void
    {
        $currentHour = new \DateTimeImmutable('2024-01-01 00:00:00');

        // 生成过去 30 天的使用记录
        for ($day = 0; $day < 30; ++$day) {
            $dayStart = $currentHour->modify("-{$day} days");

            // 每天生成 24 小时的记录
            for ($hour = 0; $hour < 24; ++$hour) {
                $usageHour = $dayStart->modify("+{$hour} hours");

                // 随机决定该小时是否有使用记录
                if (rand(1, 100) <= 70) { // 70% 概率有使用记录
                    $usage = $this->createUsageRecord($apiKey, $usageHour);
                    $manager->persist($usage);
                }
            }
        }
    }

    private function createUsageRecord(ApiKey $apiKey, \DateTimeImmutable $usageHour): ApiKeyUsage
    {
        $usage = new ApiKeyUsage();
        $usage->setApiKey($apiKey);
        $usage->setUsageHour($usageHour);

        // 随机设置终端点 ID
        $endpoints = ['chat', 'completion', 'embedding', 'moderation', 'image'];
        $usage->setEndpointId($endpoints[array_rand($endpoints)]);

        // 随机设置批处理任务 ID
        if (rand(1, 100) <= 30) { // 30% 概率是批处理任务
            $usage->setBatchJobId('batch-' . uniqid());
        }

        // 随机设置令牌数量
        $promptTokens = rand(100, 5000);
        $completionTokens = rand(50, 2000);
        $usage->setPromptTokens($promptTokens);
        $usage->setCompletionTokens($completionTokens);

        // 随机设置请求次数
        $requestCount = rand(1, 100);
        $usage->setRequestCount($requestCount);

        // 计算预估成本（假设每 1000 个 token 0.002 美元）
        $totalTokens = $promptTokens + $completionTokens;
        $estimatedCost = ($totalTokens / 1000) * 0.002;
        $usage->setEstimatedCost(number_format($estimatedCost, 4, '.', ''));

        // 设置元数据
        $metadata = [
            'model' => 'ep-' . date('Ymd') . '-' . substr(md5((string) rand()), 0, 8),
            'temperature' => round(rand(0, 100) / 100, 2),
            'max_tokens' => rand(100, 2000),
            'user_agent' => 'VolcanoArk/' . rand(1, 3) . '.0.0',
        ];
        $usage->setMetadata($metadata);

        return $usage;
    }

    public function getDependencies(): array
    {
        return [
            ApiKeyFixtures::class,
        ];
    }
}
