<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Exception;

/**
 * 通用 API 异常，用于一般的 API 错误。
 * 当抛出不需要特定异常类型的 API 相关异常时使用此类。
 */
class GenericApiException extends ApiException
{
}
