<?php

declare(strict_types=1);

namespace Tourze\VolcanoArkApiBundle\Tests\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\VolcanoArkApiBundle\Response\VolcanoArkBalanceResponse;

/**
 * @internal
 */
#[CoversClass(VolcanoArkBalanceResponse::class)]
class VolcanoArkBalanceResponseTest extends TestCase
{
    public function testFromArray(): void
    {
        $data = [
            'object' => 'balance',
            'total_granted' => 1000.0,
            'total_used' => 250.75,
            'total_available' => 749.25,
            'currency' => 'CNY',
        ];

        $response = VolcanoArkBalanceResponse::fromArray($data);

        $this->assertEquals('balance', $response->getObject());
        $this->assertNotNull($response->getBalance());

        $balance = $response->getBalance();
        $this->assertEquals(1000.0, $balance->getTotalAmount());
        $this->assertEquals(250.75, $balance->getUsedAmount());
        $this->assertEquals(749.25, $balance->getRemainingAmount());
        $this->assertEquals('CNY', $balance->getCurrency());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [
            'object' => 'balance',
            'total_granted' => 500.0,
            // Missing other fields
        ];

        $response = VolcanoArkBalanceResponse::fromArray($data);

        $this->assertEquals('balance', $response->getObject());

        $balance = $response->getBalance();
        $this->assertEquals(500.0, $balance->getTotalAmount());
        $this->assertEquals(0.0, $balance->getUsedAmount());
        $this->assertEquals(500.0, $balance->getRemainingAmount());
        $this->assertEquals('CNY', $balance->getCurrency());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $response = VolcanoArkBalanceResponse::fromArray([]);

        $this->assertEquals('balance', $response->getObject());

        $balance = $response->getBalance();
        $this->assertEquals(0.0, $balance->getTotalAmount());
        $this->assertEquals(0.0, $balance->getUsedAmount());
        $this->assertEquals(0.0, $balance->getRemainingAmount());
        $this->assertEquals('CNY', $balance->getCurrency());
    }

    public function testGetObject(): void
    {
        $data = ['object' => 'balance'];
        $response = VolcanoArkBalanceResponse::fromArray($data);

        $this->assertEquals('balance', $response->getObject());
    }

    public function testGetBalance(): void
    {
        $data = [
            'total_granted' => 100.0,
            'total_used' => 25.5,
            'total_available' => 74.5,
            'currency' => 'USD',
        ];

        $response = VolcanoArkBalanceResponse::fromArray($data);
        $balance = $response->getBalance();

        $this->assertNotNull($balance);
        $this->assertEquals(100.0, $balance->getTotalAmount());
        $this->assertEquals(25.5, $balance->getUsedAmount());
        $this->assertEquals(74.5, $balance->getRemainingAmount());
        $this->assertEquals('USD', $balance->getCurrency());
    }

    public function testJsonSerialize(): void
    {
        $data = [
            'object' => 'balance',
            'total_granted' => 1000.0,
            'total_used' => 250.75,
            'total_available' => 749.25,
            'currency' => 'CNY',
        ];

        $response = VolcanoArkBalanceResponse::fromArray($data);
        $jsonData = $response->jsonSerialize();

        $this->assertEquals('balance', $jsonData['object']);
        $this->assertArrayHasKey('balance', $jsonData);
        $balanceData = $jsonData['balance'];
        $this->assertIsArray($balanceData);
        $this->assertEquals(1000.0, $balanceData['totalAmount']);
        $this->assertEquals(250.75, $balanceData['usedAmount']);
        $this->assertEquals(749.25, $balanceData['remainingAmount']);
        $this->assertEquals('CNY', $balanceData['currency']);
    }

    public function testToArray(): void
    {
        $data = [
            'object' => 'balance',
            'total_granted' => 500.0,
            'total_used' => 125.5,
            'total_available' => 374.5,
            'currency' => 'EUR',
        ];

        $response = VolcanoArkBalanceResponse::fromArray($data);
        $arrayData = $response->toArray();

        $this->assertEquals('balance', $arrayData['object']);
        $this->assertArrayHasKey('balance', $arrayData);
        $balanceData = $arrayData['balance'];
        $this->assertIsArray($balanceData);
        $this->assertEquals(500.0, $balanceData['totalAmount']);
        $this->assertEquals(125.5, $balanceData['usedAmount']);
        $this->assertEquals(374.5, $balanceData['remainingAmount']);
        $this->assertEquals('EUR', $balanceData['currency']);
    }
}
