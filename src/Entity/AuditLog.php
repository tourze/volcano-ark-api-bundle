<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\VolcanoArkApiBundle\Repository\AuditLogRepository;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'volcano_ark_audit_logs', options: ['comment' => 'Volcano Ark 审计日志表'])]
class AuditLog implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键 ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ApiKey::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiKey $apiKey;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '操作类型'])]
    #[Assert\Length(max: 100)]
    #[Assert\NotBlank]
    #[IndexColumn]
    private string $action = '';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '操作描述'])]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    /**
     * @var array<string, mixed>|null 请求数据
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '请求数据'])]
    #[Assert\Json]
    private ?array $requestData = null;

    /**
     * @var array<string, mixed>|null 响应数据
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '响应数据'])]
    #[Assert\Json]
    private ?array $responseData = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '请求路径'])]
    #[Assert\Length(max: 255)]
    private ?string $requestPath = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '请求方法'])]
    #[Assert\Length(max: 50)]
    private ?string $requestMethod = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '客户端 IP'])]
    #[Assert\Length(max: 45)]
    #[Assert\Ip]
    private ?string $clientIp = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户代理'])]
    #[Assert\Length(max: 255)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0, 'comment' => '响应状态码'])]
    #[Assert\PositiveOrZero]
    private int $statusCode = 0;

    #[ORM\Column(type: Types::BIGINT, options: ['default' => 0, 'comment' => '响应时间（毫秒）'])]
    #[Assert\PositiveOrZero]
    private int $responseTime = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否成功'])]
    #[Assert\NotNull]
    private bool $isSuccess = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Length(max: 2000)]
    private ?string $errorMessage = null;

    /**
     * @var array<string, mixed>|null 元数据
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Json]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[Assert\NotNull]
    #[Assert\DateTime]
    #[IndexColumn]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
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

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
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
    public function getRequestData(): ?array
    {
        return $this->requestData;
    }

    /**
     * @param array<string, mixed>|null $requestData
     */
    public function setRequestData(?array $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * @param array<string, mixed>|null $responseData
     */
    public function setResponseData(?array $responseData): void
    {
        $this->responseData = $responseData;
    }

    public function getRequestPath(): ?string
    {
        return $this->requestPath;
    }

    public function setRequestPath(?string $requestPath): void
    {
        $this->requestPath = $requestPath;
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function setRequestMethod(?string $requestMethod): void
    {
        $this->requestMethod = $requestMethod;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function setClientIp(?string $clientIp): void
    {
        $this->clientIp = $clientIp;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getResponseTime(): int
    {
        return $this->responseTime;
    }

    public function setResponseTime(int $responseTime): void
    {
        $this->responseTime = $responseTime;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
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

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function __toString(): string
    {
        return sprintf(
            'AuditLog[%d] - %s: %s (%s)',
            $this->id ?? 0,
            $this->action,
            $this->description ?? 'No description',
            $this->createTime->format('Y-m-d H:i:s')
        );
    }
}
