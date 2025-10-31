<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\Response\VolcanoArkModelListResponse;

/**
 * @internal
 */
#[CoversClass(VolcanoArkModelListResponse::class)]
class VolcanoArkModelListResponseTest extends TestCase
{
    public function testFromArray(): void
    {
        $data = [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'model-1',
                    'object' => 'model',
                    'created' => 1677652288,
                    'owned_by' => 'volcano-ark',
                ],
                [
                    'id' => 'model-2',
                    'object' => 'model',
                    'created' => 1677652289,
                    'owned_by' => 'volcano-ark',
                ],
            ],
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);

        $this->assertEquals('list', $response->getObject());

        $models = $response->getData();
        $this->assertCount(2, $models);

        $this->assertEquals('model-1', $models[0]->getId());
        $this->assertEquals('model', $models[0]->getObject());
        $this->assertEquals(1677652288, $models[0]->getCreated());
        $this->assertEquals('volcano-ark', $models[0]->getOwnedBy());

        $this->assertEquals('model-2', $models[1]->getId());
        $this->assertEquals('model', $models[1]->getObject());
        $this->assertEquals(1677652289, $models[1]->getCreated());
        $this->assertEquals('volcano-ark', $models[1]->getOwnedBy());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $data = [
            'object' => 'list',
            'data' => [],
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);

        $this->assertEquals('list', $response->getObject());
        $this->assertEquals([], $response->getData());
        $this->assertCount(0, $response->getData());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'object' => 'list',
            // Missing data array
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);

        $this->assertEquals('list', $response->getObject());
        $this->assertEquals([], $response->getData());
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $response = VolcanoArkModelListResponse::fromArray([]);

        $this->assertEquals('list', $response->getObject());
        $this->assertEquals([], $response->getData());
    }

    public function testGetObject(): void
    {
        $data = ['object' => 'model_list'];
        $response = VolcanoArkModelListResponse::fromArray($data);

        $this->assertEquals('model_list', $response->getObject());
    }

    public function testGetDataWithSingleModel(): void
    {
        $data = [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'single-model',
                    'object' => 'model',
                    'created' => 1677652300,
                    'owned_by' => 'test-owner',
                ],
            ],
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);
        $models = $response->getData();

        $this->assertCount(1, $models);
        $this->assertEquals('single-model', $models[0]->getId());
        $this->assertEquals('test-owner', $models[0]->getOwnedBy());
    }

    public function testModelProperties(): void
    {
        $data = [
            'data' => [
                [
                    'id' => 'test-model-id',
                    'object' => 'test-object',
                    'created' => 1234567890,
                    'owned_by' => 'test-company',
                ],
            ],
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);
        $model = $response->getData()[0];

        $this->assertEquals('test-model-id', $model->getId());
        $this->assertEquals('test-object', $model->getObject());
        $this->assertEquals(1234567890, $model->getCreated());
        $this->assertEquals('test-company', $model->getOwnedBy());
    }

    public function testJsonSerialize(): void
    {
        $data = [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'model-1',
                    'object' => 'model',
                    'created' => 1677652288,
                    'owned_by' => 'volcano',
                ],
                [
                    'id' => 'model-2',
                    'object' => 'model',
                    'created' => 1677652290,
                    'owned_by' => 'ark',
                ],
            ],
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);
        $jsonData = $response->jsonSerialize();

        $this->assertIsArray($jsonData);
        $this->assertEquals('list', $jsonData['object']);
        $this->assertArrayHasKey('data', $jsonData);
        $this->assertIsArray($jsonData['data']);
        $this->assertCount(2, $jsonData['data']);
        $this->assertIsArray($jsonData['data'][0]);
        $this->assertEquals('model-1', $jsonData['data'][0]['id']);
        $this->assertIsArray($jsonData['data'][1]);
        $this->assertEquals('model-2', $jsonData['data'][1]['id']);
    }

    public function testToArray(): void
    {
        $data = [
            'object' => 'list',
            'data' => [
                [
                    'id' => 'test-model',
                    'object' => 'model',
                    'created' => 1677652300,
                    'owned_by' => 'test-org',
                ],
            ],
        ];

        $response = VolcanoArkModelListResponse::fromArray($data);
        $arrayData = $response->toArray();

        $this->assertIsArray($arrayData);
        $this->assertEquals('list', $arrayData['object']);
        $this->assertArrayHasKey('data', $arrayData);
        $this->assertIsArray($arrayData['data']);
        $this->assertCount(1, $arrayData['data']);
        $this->assertIsArray($arrayData['data'][0]);
        $this->assertEquals('test-model', $arrayData['data'][0]['id']);
        $this->assertEquals('test-org', $arrayData['data'][0]['owned_by']);
    }
}
