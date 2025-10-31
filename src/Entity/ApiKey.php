<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\VolcanoArkApiBundle\Repository\ApiKeyRepository;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\Table(name: 'volcano_ark_api_keys', options: ['comment' => 'Volcano Ark API 密钥表'])]
class ApiKey implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键 ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'API 密钥名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'volcano_ark', 'comment' => '服务提供商'])]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    #[IndexColumn]
    private string $provider = 'volcano_ark';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'API 密钥值'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $apiKey = '';

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '密钥值'])]
    #[Assert\Length(max: 2000)]
    #[Assert\NotBlank]
    private string $secretKey = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => 'cn-beijing', 'comment' => '区域'])]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    private string $region = 'cn-beijing';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否激活'])]
    #[Assert\NotNull]
    #[IndexColumn]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '使用次数'])]
    #[Assert\PositiveOrZero]
    private int $usageCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后使用时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $lastUsedTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    /**
     * @var array<string, mixed>|null
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): void
    {
        $this->usageCount = $usageCount;
    }

    public function incrementUsageCount(): void
    {
        ++$this->usageCount;
        $this->lastUsedTime = new \DateTimeImmutable();
    }

    public function getLastUsedTime(): ?\DateTimeImmutable
    {
        return $this->lastUsedTime;
    }

    public function setLastUsedTime(?\DateTimeImmutable $lastUsedTime): void
    {
        $this->lastUsedTime = $lastUsedTime;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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

    public function __toString(): string
    {
        return $this->name;
    }
}
