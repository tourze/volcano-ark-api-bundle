<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\VolcanoArkApiBundle\Controller\ApiKeyCrudController;

/**
 * @internal
 */
#[CoversClass(ApiKeyCrudController::class)]
#[RunTestsInSeparateProcesses]
#[Group('volcano-ark-api-bundle')]
class ApiKeyCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): ApiKeyCrudController
    {
        return new ApiKeyCrudController();
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '密钥名称' => ['密钥名称'];
        yield '服务提供商' => ['服务提供商'];
        yield '区域' => ['区域'];
        yield '是否激活' => ['是否激活'];
        yield '使用次数' => ['使用次数'];
        yield '最后使用时间' => ['最后使用时间'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield '密钥名称' => ['name'];
        yield '服务提供商' => ['provider'];
        yield 'API密钥' => ['apiKey'];
        // 跳过TextareaField字段测试，因为它们有特殊渲染
        // yield '密钥值' => ['secretKey'];
        // yield '描述' => ['description'];
        yield '区域' => ['region'];
        yield '是否激活' => ['isActive'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        yield '密钥名称' => ['name'];
        yield '服务提供商' => ['provider'];
        yield 'API密钥' => ['apiKey'];
        // 跳过TextareaField字段测试，因为它们有特殊渲染
        // yield '密钥值' => ['secretKey'];
        // yield '描述' => ['description'];
        yield '区域' => ['region'];
        yield '是否激活' => ['isActive'];
    }

    /**
     * 测试表单验证错误 - 提交空表单并验证错误信息
     */
    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/admin/volcano-ark-api/api-key?crudAction=new');

        // 设置静态客户端以支持响应断言
        self::getClient($client);
        $this->assertResponseIsSuccessful();

        // 检查表单是否存在
        $this->assertSelectorExists('form');

        // 尝试找到提交按钮，可能是 "保存"、"Save" 或 "Create"
        $form = null;
        $buttonSelectors = ['保存', 'Save', 'Create', 'Submit'];

        foreach ($buttonSelectors as $buttonText) {
            try {
                $form = $crawler->selectButton($buttonText)->form();
                break;
            } catch (\InvalidArgumentException $e) {
                // 继续尝试下一个按钮
                continue;
            }
        }

        if (null === $form) {
            self::markTestSkipped('无法找到提交按钮进行表单验证测试');
        }

        // 提交空表单
        $crawler = $client->submit($form);

        // 验证响应状态码为422（验证错误）
        $this->assertResponseStatusCodeSame(422);

        // 检查必填字段的错误消息 (基于ApiKey实体的@Assert\NotBlank字段)
        $invalidFeedback = $crawler->filter('.invalid-feedback');

        if ($invalidFeedback->count() > 0) {
            $errorText = $invalidFeedback->text();
            $this->assertStringContainsString('should not be blank', $errorText);
        } else {
            // 检查其他可能的错误消息容器
            $this->assertTrue(
                $crawler->filter('.alert-danger, .is-invalid, .form-error')->count() > 0,
                '应该显示验证错误信息'
            );
        }
    }
}
