# Volcano Ark API Bundle

[English](README.md) | [中文](README.zh-CN.md)

一个用于集成 Volcano Ark（火山方舟）API 的 Symfony Bundle，提供了完整的 API 密钥管理、使用量统计和审计日志功能。

## 功能特性

- 🔑 **API 密钥管理** - 支持多个 API 密钥的创建、激活、停用和自动轮换
- 📊 **使用量统计** - 实时统计 API 调用次数、Token 使用量和成本估算
- 🔍 **审计日志** - 完整的 API 调用审计追踪
- 🎯 **OpenAI 兼容** - 提供兼容 OpenAI SDK 的客户端接口
- 🖥️ **管理后台** - 集成 EasyAdmin 的完整管理界面
- 🛠️ **命令行工具** - 丰富的 CLI 命令支持
- 🔄 **自动同步** - 支持使用量和审计日志的自动同步

## 安装

```bash
composer require tourze/volcano-ark-api-bundle
```

## 配置

### 基础配置

```yaml
# config/packages/volcano_ark_api.yaml
tourze_volcano_ark_api:
  base_url: 'https://ark.cn-beijing.volces.com/api/v3'
  default_region: 'cn-beijing'
```

### Doctrine 配置

```yaml
# config/packages/doctrine.yaml
doctrine:
  dbal:
    # ... 您的数据库配置
  orm:
    # ... 您的 ORM 配置
    mappings:
      VolcanoArkApiBundle:
        type: attribute
        is_bundle: true
        dir: '%kernel.project_dir%/vendor/tourze/volcano-ark-api-bundle/src/Entity'
        prefix: 'Tourze\VolcanoArkApiBundle\Entity'
        alias: VolcanoArkApi
```

## 使用方法

### 命令行工具

#### API 密钥管理

```bash
# 列出所有 API 密钥
php bin/console volcano:api-key:manage list

# 创建新的 API 密钥
php bin/console volcano:api-key:manage create \
  --name="生产环境密钥" \
  --api-key="your-api-key" \
  --secret-key="your-secret-key" \
  --region="cn-beijing"

# 激活 API 密钥
php bin/console volcano:api-key:manage activate --name="生产环境密钥"

# 停用 API 密钥
php bin/console volcano:api-key:manage deactivate --name="生产环境密钥"

# 删除 API 密钥
php bin/console volcano:api-key:manage delete --name="生产环境密钥"
```

#### 数据同步

```bash
# 同步使用量数据
php bin/console volcano:usage:sync

# 同步审计日志
php bin/console volcano:sync:audit-logs
```

### 服务层使用

#### 使用 ApiKeyService

```php
use Tourze\VolcanoArkApiBundle\Service\ApiKeyService;

class MyService
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
    }

    public function createApiKey(): void
    {
        $key = $this->apiKeyService->createKey(
            'My API Key',
            'api-key-value',
            'secret-key-value',
            'cn-beijing'
        );

        $this->apiKeyService->activateKey($key);
    }

    public function getCurrentKey(): \Tourze\VolcanoArkApiBundle\Entity\ApiKey
    {
        return $this->apiKeyService->getCurrentKey();
    }
}
```

#### 使用 UsageService

```php
use Tourze\VolcanoArkApiBundle\Service\UsageService;

class UsageController
{
    public function __construct(
        private readonly UsageService $usageService,
    ) {
    }

    public function getUsageData(): array
    {
        $startTime = strtotime('-7 days');
        $endTime = time();

        return $this->usageService->getUsage(
            startTime: $startTime,
            endTime: $endTime,
            interval: 3600, // 1小时间隔
            scenes: ['chat'],
            projectName: 'my-project'
        );
    }
}
```

#### 使用 AuditLogService

```php
use Tourze\VolcanoArkApiBundle\Service\AuditLogService;

class AuditController
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function getAuditLogs(): array
    {
        $startTime = strtotime('-7 days');
        $endTime = time();

        return $this->auditLogService->getAuditLogs(
            startTime: $startTime,
            endTime: $endTime,
            limit: 100
        );
    }
}
```

#### OpenAI 兼容客户端

```php
use Tourze\VolcanoArkApiBundle\Service\VolcanoArkOpenAiClient;

class ChatController
{
    public function __construct(
        private readonly VolcanoArkOpenAiClient $client,
    ) {
    }

    public function chat(string $message): array
    {
        $response = $this->client->chatCompletion([
            'model' => 'your-model-id',
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        return $response->getChoices();
    }
}
```

### 自定义客户端

```php
use Tourze\VolcanoArkApiBundle\Client\VolcanoArkApiClient;
use Tourze\VolcanoArkApiBundle\Request\VolcanoArkChatCompletionRequest;

class CustomService
{
    public function __construct(
        private readonly VolcanoArkApiClient $client,
    ) {
    }

    public function customRequest(): array
    {
        $request = new VolcanoArkChatCompletionRequest([
            'model' => 'your-model-id',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!']
            ],
        ]);

        return $this->client->send($request);
    }
}
```

## 数据模型

### ApiKey

API 密钥实体，包含以下字段：
- `id`: 主键 ID
- `name`: 密钥名称
- `provider`: 服务提供商（默认：volcano_ark）
- `apiKey`: API 密钥值（加密存储）
- `secretKey`: 密钥值（加密存储）
- `region`: 区域（默认：cn-beijing）
- `isActive`: 是否激活状态
- `usageCount`: 使用次数统计
- `lastUsedTime`: 最后使用时间
- `description`: 描述信息
- `metadata`: 扩展元数据（JSON 格式）
- `createdAt`: 创建时间
- `updatedAt`: 更新时间

### ApiKeyUsage

API 密钥使用统计实体，包含以下字段：
- `id`: 主键 ID
- `apiKey`: 关联的 API 密钥
- `usageHour`: 使用小时（按小时聚合）
- `endpointId`: 端点 ID
- `promptTokens`: 提示 Token 数量
- `completionTokens`: 完成 Token 数量
- `totalTokens`: 总 Token 数量
- `requestCount`: 请求次数
- `estimatedCost`: 估算成本
- `createdAt`: 创建时间
- `updatedAt`: 更新时间

### AuditLog

审计日志实体，包含以下字段：
- `id`: 主键 ID
- `apiKey`: 关联的 API 密钥
- `timestamp`: 请求时间戳
- `method`: 请求方法
- `endpoint`: 请求端点
- `requestId`: 请求 ID
- `promptTokens`: 提示 Token 数量
- `completionTokens`: 完成 Token 数量
- `totalTokens`: 总 Token 数量
- `model`: 使用的模型
- `cost`: 本次请求成本
- `metadata`: 额外元数据（JSON 格式）
- `createdAt`: 创建时间
- `updatedAt`: 更新时间

## 管理后台

Bundle 集成了 EasyAdmin，提供完整的管理界面：

- **API 密钥管理** - 查看、创建、编辑、激活/停用 API 密钥
- **使用量统计** - 查看详细的使用量报告和图表
- **审计日志** - 浏览和搜索完整的审计记录

访问 `/admin` 即可进入管理后台。

## 事件系统

Bundle 提供了丰富的事件系统，允许您在关键节点执行自定义逻辑：

### ApiKeyUsedEvent

当 API 密钥被使用时触发：

```php
use Tourze\VolcanoArkApiBundle\Event\ApiKeyUsedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiKeyUsageSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ApiKeyUsedEvent::class => 'onApiKeyUsed',
        ];
    }

    public function onApiKeyUsed(ApiKeyUsedEvent $event): void
    {
        $apiKey = $event->getApiKey();
        $request = $event->getRequest();

        // 执行您的自定义逻辑
    }
}
```

### UsageSyncedEvent

当使用量数据同步完成时触发。

### AuditLogSyncedEvent

当审计日志同步完成时触发。

## 测试

```bash
# 运行测试
composer run test

# 运行 PHPStan 静态分析
composer run phpstan

# 运行代码格式检查
composer run cs-fix
```

## 性能优化

### 缓存策略

Bundle 内置了多种缓存策略来优化性能：

- **API 密钥缓存** - 缓存当前激活的 API 密钥
- **使用量缓存** - 缓存频繁查询的使用量数据
- **模型列表缓存** - 缓存可用的模型列表

### 异步处理

对于大量的数据同步操作，建议使用队列系统：

```yaml
# config/packages/framework.yaml
framework:
  messenger:
    transports:
      async: 'doctrine://default'
    routing:
      'Tourze\VolcanoArkApiBundle\Message\SyncUsageMessage': async
```

## 安全考虑

1. **密钥加密** - 所有 API 密钥和密钥值在数据库中都经过加密存储
2. **访问控制** - 管理后台需要适当的权限验证
3. **审计追踪** - 所有 API 调用都有完整的审计日志
4. **密钥轮换** - 支持自动和手动的密钥轮换

## 故障排除

### 常见问题

1. **No active API key found**
    - 确保至少有一个激活的 API 密钥
    - 检查密钥的 `isActive` 字段是否为 `true`

2. **API request failed**
    - 验证 API 密钥和密钥值是否正确
    - 检查网络连接和防火墙设置
    - 确认区域设置是否正确

3. **Database connection errors**
    - 确保 Doctrine 配置正确
    - 检查数据库连接和权限

### 调试模式

启用调试模式获取更详细的日志信息：

```yaml
# config/packages/dev/monolog.yaml
monolog:
  handlers:
    volcano_ark:
      type: stream
      level: debug
      channels: ['volcano_ark']
      path: '%kernel.logs_dir%/volcano_ark.log'
```

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT