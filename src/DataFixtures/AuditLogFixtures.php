<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\VolcanoArkApiBundle\Entity\ApiKey;
use Tourze\VolcanoArkApiBundle\Entity\AuditLog;

class AuditLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 使用引用获取 API 密钥
        $apiKey1 = $this->getReference(ApiKeyFixtures::API_KEY_1_REFERENCE, ApiKey::class);
        $apiKey2 = $this->getReference(ApiKeyFixtures::API_KEY_2_REFERENCE, ApiKey::class);

        if ($apiKey1 instanceof ApiKey) {
            $this->createAuditLogs($manager, $apiKey1);
        }

        if ($apiKey2 instanceof ApiKey) {
            $this->createAuditLogs($manager, $apiKey2);
        }

        $manager->flush();
    }

    private function createAuditLogs(ObjectManager $manager, ApiKey $apiKey): void
    {
        $actions = [
            'api_request',
            'user_login',
            'user_logout',
            'data_access',
            'config_change',
            'batch_job_start',
            'batch_job_complete',
            'error_occurred',
            'rate_limit_exceeded',
            'invalid_token',
        ];

        $endpoints = [
            '/api/v1/chat',
            '/api/v1/completion',
            '/api/v1/embedding',
            '/api/v1/moderation',
            '/api/v1/image',
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/user/profile',
            '/api/v1/usage/stats',
        ];

        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        $currentDateTime = new \DateTimeImmutable('2024-01-01 00:00:00');

        // 生成过去 30 天的审计日志
        for ($day = 0; $day < 30; ++$day) {
            $dayStart = $currentDateTime->modify("-{$day} days");

            // 每天生成随机数量的日志记录
            $dailyLogs = rand(10, 100);

            for ($i = 0; $i < $dailyLogs; ++$i) {
                $log = $this->createAuditLog($apiKey, $actions, $endpoints, $methods);
                $manager->persist($log);
            }
        }
    }

    /**
     * @param array<string> $actions
     * @param array<string> $endpoints
     * @param array<string> $methods
     */
    private function createAuditLog(
        ApiKey $apiKey,
        array $actions,
        array $endpoints,
        array $methods,
    ): AuditLog {
        $log = new AuditLog();
        $log->setApiKey($apiKey);

        // 随机选择动作
        $action = $actions[array_rand($actions)];
        $log->setAction($action);

        // 根据动作设置描述
        $log->setDescription($this->generateDescription($action));

        // 随机设置请求信息
        if (rand(1, 100) <= 80) { // 80% 概率有请求路径
            $log->setRequestPath($endpoints[array_rand($endpoints)]);
            $log->setRequestMethod($methods[array_rand($methods)]);
        }

        // 随机设置客户端信息
        if (rand(1, 100) <= 90) { // 90% 概率有客户端 IP
            $log->setClientIp($this->generateRandomIp());
        }

        if (rand(1, 100) <= 70) { // 70% 概率有用户代理
            $log->setUserAgent($this->generateRandomUserAgent());
        }

        // 随机设置请求和响应数据
        if (rand(1, 100) <= 60) { // 60% 概率有请求数据
            $log->setRequestData($this->generateRandomRequestData());
        }

        if (rand(1, 100) <= 60) { // 60% 概率有响应数据
            $log->setResponseData($this->generateRandomResponseData());
        }

        // 随机设置状态码和响应时间
        $isSuccess = rand(1, 100) <= 85; // 85% 概率成功
        $log->setIsSuccess($isSuccess);

        if ($isSuccess) {
            $statusCode = [200, 201, 202, 204][array_rand([200, 201, 202, 204])];
            $log->setStatusCode($statusCode);
            $log->setErrorMessage(null);
        } else {
            $statusCode = [400, 401, 403, 404, 429, 500][array_rand([400, 401, 403, 404, 429, 500])];
            $log->setStatusCode($statusCode);
            $log->setErrorMessage($this->generateErrorMessage($statusCode));
        }

        // 随机设置响应时间
        $responseTime = rand(50, 5000);
        $log->setResponseTime($responseTime);

        // 设置元数据
        $log->setMetadata($this->generateRandomMetadata());

        return $log;
    }

    private function generateDescription(string $action): string
    {
        $descriptions = [
            'api_request' => 'API 请求处理',
            'user_login' => '用户登录',
            'user_logout' => '用户登出',
            'data_access' => '数据访问',
            'config_change' => '配置变更',
            'batch_job_start' => '批处理任务开始',
            'batch_job_complete' => '批处理任务完成',
            'error_occurred' => '发生错误',
            'rate_limit_exceeded' => '超出速率限制',
            'invalid_token' => '无效令牌',
        ];

        return $descriptions[$action] ?? '未知操作';
    }

    private function generateRandomIp(): string
    {
        return sprintf(
            '%d.%d.%d.%d',
            rand(1, 255),
            rand(0, 255),
            rand(0, 255),
            rand(1, 254)
        );
    }

    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'PostmanRuntime/7.32.2',
            'curl/7.81.0',
            'VolcanoArk/1.0.0',
            'Python/3.9 requests/2.28.1',
        ];

        return $userAgents[array_rand($userAgents)];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateRandomRequestData(): array
    {
        $data = [
            'timestamp' => time(),
            'request_id' => uniqid('req_', true),
        ];

        if (rand(1, 100) <= 50) {
            $data['user_id'] = rand(1, 1000);
        }

        if (rand(1, 100) <= 30) {
            $data['session_id'] = uniqid('sess_', true);
        }

        if (rand(1, 100) <= 20) {
            $data['filters'] = [
                'limit' => rand(10, 100),
                'offset' => rand(0, 50),
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateRandomResponseData(): array
    {
        $data = [
            'timestamp' => time(),
            'response_id' => uniqid('resp_', true),
            'status' => 'success',
        ];

        if (rand(1, 100) <= 40) {
            $data['data'] = [
                'count' => rand(1, 100),
                'items' => array_fill(0, rand(1, 10), 'sample_item'),
            ];
        }

        if (rand(1, 100) <= 20) {
            $data['metadata'] = [
                'page' => rand(1, 10),
                'total_pages' => rand(1, 20),
            ];
        }

        return $data;
    }

    private function generateErrorMessage(int $statusCode): string
    {
        $messages = [
            400 => '请求参数错误',
            401 => '未授权访问',
            403 => '禁止访问',
            404 => '资源不存在',
            429 => '请求过于频繁',
            500 => '服务器内部错误',
        ];

        return $messages[$statusCode] ?? '未知错误';
    }

    /**
     * @return array<string, mixed>
     */
    private function generateRandomMetadata(): array
    {
        $metadata = [
            'environment' => ['development', 'staging', 'production'][array_rand(['development', 'staging', 'production'])],
            'version' => '1.0.' . rand(0, 9),
        ];

        if (rand(1, 100) <= 30) {
            $metadata['debug'] = true;
        }

        if (rand(1, 100) <= 20) {
            $metadata['correlation_id'] = uniqid('corr_', true);
        }

        return $metadata;
    }

    public function getDependencies(): array
    {
        return [
            ApiKeyFixtures::class,
        ];
    }
}
