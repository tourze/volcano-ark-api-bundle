<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyUsageRepository;

#[ORM\Entity(repositoryClass: ApiKeyUsageRepository::class)]
#[ORM\Table(name: 'volcano_ark_api_key_usages', options: ['comment' => 'API密钥使用统计表'])]
#[ORM\UniqueConstraint(name: 'uniq_key_hour_endpoint', columns: ['api_key_id', 'usage_hour', 'endpoint_id'])]
class ApiKeyUsage implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键 ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ApiKey::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiKey $apiKey;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '使用小时'])]
    #[Assert\NotNull]
    #[Assert\DateTime]
    #[IndexColumn]
    private \DateTimeImmutable $usageHour;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '终端点 ID'])]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private ?string $endpointId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '批处理任务 ID'])]
    #[Assert\Length(max: 255)]
    private ?string $batchJobId = null;

    #[ORM\Column(type: Types::BIGINT, options: ['default' => 0, 'comment' => '提示词数量'])]
    #[Assert\PositiveOrZero]
    private int $promptTokens = 0;

    #[ORM\Column(type: Types::BIGINT, options: ['default' => 0, 'comment' => '完成词数量'])]
    #[Assert\PositiveOrZero]
    private int $completionTokens = 0;

    #[ORM\Column(type: Types::BIGINT, options: ['default' => 0, 'comment' => '总词数'])]
    #[Assert\PositiveOrZero]
    private int $totalTokens = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '请求次数'])]
    #[Assert\PositiveOrZero]
    private int $requestCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, options: ['default' => 0, 'comment' => '预估成本'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 15)]
    private string $estimatedCost = '0.0000';

    /**
     * @var array<string, mixed>|null 元数据
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Json]
    private ?array $metadata = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function setApiKey(ApiKey $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getUsageHour(): \DateTimeImmutable
    {
        return $this->usageHour;
    }

    public function setUsageHour(\DateTimeImmutable $usageHour): void
    {
        $this->usageHour = $usageHour;
    }

    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    public function setEndpointId(?string $endpointId): void
    {
        $this->endpointId = $endpointId;
    }

    public function getBatchJobId(): ?string
    {
        return $this->batchJobId;
    }

    public function setBatchJobId(?string $batchJobId): void
    {
        $this->batchJobId = $batchJobId;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(int $promptTokens): void
    {
        $this->promptTokens = $promptTokens;
        $this->updateTotalTokens();
    }

    private function updateTotalTokens(): void
    {
        $this->totalTokens = $this->promptTokens + $this->completionTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(int $completionTokens): void
    {
        $this->completionTokens = $completionTokens;
        $this->updateTotalTokens();
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(int $totalTokens): void
    {
        $this->totalTokens = $totalTokens;
    }

    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    public function setRequestCount(int $requestCount): void
    {
        $this->requestCount = $requestCount;
    }

    public function getEstimatedCost(): string
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(string $estimatedCost): void
    {
        $this->estimatedCost = $estimatedCost;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addUsage(int $promptTokens, int $completionTokens): void
    {
        $this->promptTokens += $promptTokens;
        $this->completionTokens += $completionTokens;
        $this->updateTotalTokens();
        $this->incrementRequestCount();
    }

    public function incrementRequestCount(): void
    {
        ++$this->requestCount;
    }

    public function __toString(): string
    {
        return sprintf(
            'ApiKeyUsage[%d] - %s: %d tokens',
            $this->id ?? 0,
            $this->usageHour->format('Y-m-d H:i:s'),
            $this->totalTokens
        );
    }
}
