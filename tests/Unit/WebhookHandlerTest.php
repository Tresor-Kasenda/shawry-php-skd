<?php

declare(strict_types=1);

use Shwary\DTOs\Transaction;
use Shwary\Exceptions\ShwaryException;
use Shwary\Webhook\WebhookHandler;

describe('WebhookHandler', function () {

    describe('parsePayload', function () {
        it('parses valid webhook payload with camelCase keys', function () {
            $handler = new WebhookHandler();
            $payload = json_encode([
                'id' => 'txn_webhook_123',
                'userId' => 'user_456',
                'amount' => 5000,
                'currency' => 'CDF',
                'type' => 'deposit',
                'status' => 'completed',
                'recipientPhoneNumber' => '+243970000000',
                'referenceId' => 'ref_abc',
                'metadata' => ['order_id' => '12345'],
                'createdAt' => '2026-02-05T10:00:00+00:00',
                'updatedAt' => '2026-02-05T10:30:00+00:00',
                'completedAt' => '2026-02-05T10:30:00+00:00',
                'isSandbox' => false,
            ]);

            $transaction = $handler->parsePayload($payload);

            expect($transaction)->toBeInstanceOf(Transaction::class)
                ->and($transaction->id)->toBe('txn_webhook_123')
                ->and($transaction->userId)->toBe('user_456')
                ->and($transaction->amount)->toBe(5000)
                ->and($transaction->isCompleted())->toBeTrue();
        });

        it('parses valid webhook payload with snake_case keys', function () {
            $handler = new WebhookHandler();
            $payload = json_encode([
                'id' => 'txn_snake_123',
                'user_id' => 'user_789',
                'amount' => 10000,
                'currency' => 'KES',
                'type' => 'deposit',
                'status' => 'pending',
                'recipient_phone_number' => '+254700000000',
                'reference_id' => 'ref_def',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
                'is_sandbox' => true,
            ]);

            $transaction = $handler->parsePayload($payload);

            expect($transaction)->toBeInstanceOf(Transaction::class)
                ->and($transaction->id)->toBe('txn_snake_123')
                ->and($transaction->userId)->toBe('user_789')
                ->and($transaction->isSandbox)->toBeTrue();
        });

        it('throws exception for invalid JSON', function () {
            $handler = new WebhookHandler();
            $handler->parsePayload('invalid json');
        })->throws(ShwaryException::class, 'Invalid webhook payload');

        it('throws exception for missing transaction ID', function () {
            $handler = new WebhookHandler();
            $payload = json_encode([
                'userId' => 'user_123',
                'amount' => 5000,
            ]);

            $handler->parsePayload($payload);
        })->throws(ShwaryException::class, 'missing transaction ID');
    });

    describe('isTerminalStatus', function () {
        it('returns true for completed transaction', function () {
            $handler = new WebhookHandler();
            $transaction = Transaction::fromArray([
                'id' => 'txn_1',
                'user_id' => 'user_1',
                'amount' => 5000,
                'currency' => 'CDF',
                'status' => 'completed',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_1',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]);

            expect($handler->isTerminalStatus($transaction))->toBeTrue();
        });

        it('returns true for failed transaction', function () {
            $handler = new WebhookHandler();
            $transaction = Transaction::fromArray([
                'id' => 'txn_2',
                'user_id' => 'user_2',
                'amount' => 5000,
                'currency' => 'CDF',
                'status' => 'failed',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_2',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]);

            expect($handler->isTerminalStatus($transaction))->toBeTrue();
        });

        it('returns false for pending transaction', function () {
            $handler = new WebhookHandler();
            $transaction = Transaction::fromArray([
                'id' => 'txn_3',
                'user_id' => 'user_3',
                'amount' => 5000,
                'currency' => 'CDF',
                'status' => 'pending',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_3',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]);

            expect($handler->isTerminalStatus($transaction))->toBeFalse();
        });
    });

    describe('createResponse', function () {
        it('creates success response with default message', function () {
            $handler = new WebhookHandler();
            $response = $handler->createResponse(true);

            expect($response)->toBeArray()
                ->and($response['success'])->toBeTrue()
                ->and($response['message'])->toBe('Webhook processed successfully')
                ->and($response)->toHaveKey('timestamp');
        });

        it('creates success response with custom message', function () {
            $handler = new WebhookHandler();
            $response = $handler->createResponse(true, 'Payment confirmed');

            expect($response['success'])->toBeTrue()
                ->and($response['message'])->toBe('Payment confirmed');
        });

        it('creates failure response with default message', function () {
            $handler = new WebhookHandler();
            $response = $handler->createResponse(false);

            expect($response['success'])->toBeFalse()
                ->and($response['message'])->toBe('Webhook processing failed');
        });

        it('creates failure response with custom message', function () {
            $handler = new WebhookHandler();
            $response = $handler->createResponse(false, 'Invalid signature');

            expect($response['success'])->toBeFalse()
                ->and($response['message'])->toBe('Invalid signature');
        });

        it('includes ISO 8601 timestamp', function () {
            $handler = new WebhookHandler();
            $response = $handler->createResponse(true);

            expect($response['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });
    });

});
