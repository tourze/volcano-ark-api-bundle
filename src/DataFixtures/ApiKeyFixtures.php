<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;

class ApiKeyFixtures extends Fixture
{
    public const API_KEY_1_REFERENCE = 'api-key-1';
    public const API_KEY_2_REFERENCE = 'api-key-2';

    public function load(ObjectManager $manager): void
    {
        // 创建测试用的 API Key
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setProvider('volcano_ark');
        $apiKey->setApiKey('test_api_key_123');
        $apiKey->setSecretKey('test_secret_key_456');
        $apiKey->setRegion('cn-beijing');
        $apiKey->setDescription('Test API key for development');
        $apiKey->setMetadata(['env' => 'test', 'purpose' => 'development']);

        $manager->persist($apiKey);
        $this->addReference(self::API_KEY_1_REFERENCE, $apiKey);

        // 创建第二个测试用的 API Key
        $apiKey2 = new ApiKey();
        $apiKey2->setName('Production API Key');
        $apiKey2->setProvider('volcano_ark');
        $apiKey2->setApiKey('prod_api_key_789');
        $apiKey2->setSecretKey('prod_secret_key_012');
        $apiKey2->setRegion('cn-beijing');
        $apiKey2->setDescription('Production API key');
        $apiKey2->setMetadata(['env' => 'prod', 'purpose' => 'production']);

        $manager->persist($apiKey2);
        $this->addReference(self::API_KEY_2_REFERENCE, $apiKey2);

        $manager->flush();
    }
}
