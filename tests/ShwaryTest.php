<?php

declare(strict_types=1);

namespace Shwary\Tests;

use PHPUnit\Framework\TestCase;
use Shwary\Config;
use Shwary\DTOs\PaymentRequest;
use Shwary\DTOs\Transaction;
use Shwary\Enums\Country;
use Shwary\Enums\TransactionStatus;
use Shwary\Exceptions\ValidationException;
use Shwary\Webhook\WebhookHandler;

final class ShwaryTest extends TestCase
{
    public function test_config_creation(): void
    {
        $config = new Config(
            merchantId: 'test-merchant-id',
            merchantKey: 'test-merchant-key',
        );

        $this->assertEquals('test-merchant-id', $config->getMerchantId());
        $this->assertEquals('test-merchant-key', $config->getMerchantKey());
        $this->assertEquals('https://api.shwary.com', $config->getBaseUrl());
        $this->assertFalse($config->isSandbox());
    }

    public function test_config_from_array(): void
    {
        $config = Config::fromArray([
            'merchant_id' => 'array-merchant-id',
            'merchant_key' => 'array-merchant-key',
            'sandbox' => true,
        ]);

        $this->assertEquals('array-merchant-id', $config->getMerchantId());
        $this->assertTrue($config->isSandbox());
    }

    public function test_country_enum(): void
    {
        $drc = Country::DRC;

        $this->assertEquals('DRC', $drc->value);
        $this->assertEquals('CDF', $drc->getCurrency());
        $this->assertEquals('+243', $drc->getDialCode());
        $this->assertEquals(2900, $drc->getMinimumAmount());
    }

    public function test_payment_request_creation(): void
    {
        $request = PaymentRequest::create(
            amount: 5000,
            phone: '+243812345678',
            country: Country::DRC,
            callbackUrl: 'https://example.com/callback'
        );

        $this->assertEquals(5000, $request->amount);
        $this->assertEquals('+243812345678', $request->clientPhoneNumber);
        $this->assertEquals(Country::DRC, $request->country);
        $this->assertEquals('https://example.com/callback', $request->callbackUrl);
    }

    public function test_payment_request_validation_amount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Amount must be greater than 2900');

        PaymentRequest::create(
            amount: 2900,
            phone: '+243812345678',
            country: Country::DRC,
        );
    }

    public function test_payment_request_validation_phone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid phone number');

        PaymentRequest::create(
            amount: 5000,
            phone: '+254812345678', // Kenya prefix for DRC
            country: Country::DRC,
        );
    }

    public function test_payment_request_validation_callback_url(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid callback URL');

        PaymentRequest::create(
            amount: 5000,
            phone: '+243812345678',
            country: Country::DRC,
            callbackUrl: 'http://insecure.com/callback' // HTTP instead of HTTPS
        );
    }

    public function test_payment_request_to_array(): void
    {
        $request = PaymentRequest::create(
            amount: 5000,
            phone: '+243812345678',
            country: Country::DRC,
            callbackUrl: 'https://example.com/callback'
        );

        $array = $request->toArray();

        $this->assertEquals([
            'amount' => 5000,
            'clientPhoneNumber' => '+243812345678',
            'callbackUrl' => 'https://example.com/callback',
        ], $array);
    }

    public function test_transaction_from_array(): void
    {
        $data = [
            'id' => 'tx-123',
            'userId' => 'merchant-uuid',
            'amount' => 5000,
            'currency' => 'CDF',
            'type' => 'deposit',
            'status' => 'pending',
            'recipientPhoneNumber' => '+243812345678',
            'referenceId' => 'ref-123',
            'metadata' => null,
            'failureReason' => null,
            'completedAt' => null,
            'createdAt' => '2025-01-16T10:15:00.000Z',
            'updatedAt' => '2025-01-16T10:15:00.000Z',
            'isSandbox' => false,
        ];

        $transaction = Transaction::fromArray($data);

        $this->assertEquals('tx-123', $transaction->id);
        $this->assertEquals(5000, $transaction->amount);
        $this->assertEquals(TransactionStatus::PENDING, $transaction->status);
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isCompleted());
        $this->assertFalse($transaction->isFailed());
    }

    public function test_transaction_status_methods(): void
    {
        $pendingData = $this->getTransactionData(['status' => 'pending']);
        $completedData = $this->getTransactionData(['status' => 'completed']);
        $failedData = $this->getTransactionData(['status' => 'failed']);

        $pending = Transaction::fromArray($pendingData);
        $completed = Transaction::fromArray($completedData);
        $failed = Transaction::fromArray($failedData);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isTerminal());

        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($completed->isTerminal());

        $this->assertTrue($failed->isFailed());
        $this->assertTrue($failed->isTerminal());
    }

    public function test_webhook_handler_parse_payload(): void
    {
        $handler = new WebhookHandler();
        $payload = json_encode($this->getTransactionData(['status' => 'completed']));

        $transaction = $handler->parsePayload($payload);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertTrue($transaction->isCompleted());
    }

    public function test_webhook_handler_invalid_payload(): void
    {
        $handler = new WebhookHandler();

        $this->expectException(\Shwary\Exceptions\ShwaryException::class);
        $handler->parsePayload('invalid json');
    }

    public function test_webhook_handler_create_response(): void
    {
        $handler = new WebhookHandler();

        $success = $handler->createResponse(true);
        $failure = $handler->createResponse(false, 'Custom error');

        $this->assertTrue($success['success']);
        $this->assertFalse($failure['success']);
        $this->assertEquals('Custom error', $failure['message']);
    }

    private function getTransactionData(array $overrides = []): array
    {
        return array_merge([
            'id' => 'tx-123',
            'userId' => 'merchant-uuid',
            'amount' => 5000,
            'currency' => 'CDF',
            'type' => 'deposit',
            'status' => 'pending',
            'recipientPhoneNumber' => '+243812345678',
            'referenceId' => 'ref-123',
            'metadata' => null,
            'failureReason' => null,
            'completedAt' => null,
            'createdAt' => '2025-01-16T10:15:00.000Z',
            'updatedAt' => '2025-01-16T10:15:00.000Z',
            'isSandbox' => false,
        ], $overrides);
    }
}
