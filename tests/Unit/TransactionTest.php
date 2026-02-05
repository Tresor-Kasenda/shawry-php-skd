<?php

declare(strict_types=1);

use Shwary\DTOs\Transaction;
use Shwary\Enums\TransactionStatus;

describe('Transaction DTO', function () {
    
    it('creates transaction from camelCase array keys', function () {
        $data = [
            'id' => 'txn_123456',
            'userId' => 'user_789',
            'amount' => 5000,
            'currency' => 'CDF',
            'type' => 'deposit',
            'status' => 'pending',
            'recipientPhoneNumber' => '+243970000000',
            'referenceId' => 'ref_abc123',
            'metadata' => ['order_id' => '12345'],
            'failureReason' => null,
            'completedAt' => null,
            'createdAt' => '2026-02-04T10:00:00+00:00',
            'updatedAt' => '2026-02-04T10:00:00+00:00',
            'isSandbox' => true,
            'pretiumTransactionId' => 'pretium_xyz',
        ];

        $transaction = Transaction::fromArray($data);

        expect($transaction->id)->toBe('txn_123456')
            ->and($transaction->userId)->toBe('user_789')
            ->and($transaction->amount)->toBe(5000)
            ->and($transaction->currency)->toBe('CDF')
            ->and($transaction->type)->toBe('deposit')
            ->and($transaction->status)->toBe(TransactionStatus::PENDING)
            ->and($transaction->recipientPhoneNumber)->toBe('+243970000000')
            ->and($transaction->referenceId)->toBe('ref_abc123')
            ->and($transaction->metadata)->toBe(['order_id' => '12345'])
            ->and($transaction->createdAt->format('Y-m-d'))->toBe('2026-02-04')
            ->and($transaction->updatedAt->format('Y-m-d'))->toBe('2026-02-04')
            ->and($transaction->isSandbox)->toBeTrue()
            ->and($transaction->pretiumTransactionId)->toBe('pretium_xyz');
    });

    it('creates transaction from snake_case array keys', function () {
        $data = [
            'id' => 'txn_789012',
            'user_id' => 'user_456',
            'amount' => 10000,
            'currency' => 'USD',
            'type' => 'withdrawal',
            'status' => 'completed',
            'recipient_phone_number' => '+254700000000',
            'reference_id' => 'ref_def456',
            'metadata' => null,
            'failure_reason' => null,
            'completed_at' => '2026-02-04T12:00:00+00:00',
            'created_at' => '2026-02-04T10:00:00+00:00',
            'updated_at' => '2026-02-04T12:00:00+00:00',
            'is_sandbox' => false,
            'pretium_transaction_id' => 'pretium_abc',
        ];

        $transaction = Transaction::fromArray($data);

        expect($transaction->id)->toBe('txn_789012')
            ->and($transaction->userId)->toBe('user_456')
            ->and($transaction->amount)->toBe(10000)
            ->and($transaction->currency)->toBe('USD')
            ->and($transaction->type)->toBe('withdrawal')
            ->and($transaction->status)->toBe(TransactionStatus::COMPLETED)
            ->and($transaction->recipientPhoneNumber)->toBe('+254700000000')
            ->and($transaction->referenceId)->toBe('ref_def456')
            ->and($transaction->completedAt)->not->toBeNull()
            ->and($transaction->completedAt->format('Y-m-d H:i:s'))->toBe('2026-02-04 12:00:00')
            ->and($transaction->createdAt->format('Y-m-d'))->toBe('2026-02-04')
            ->and($transaction->updatedAt->format('Y-m-d'))->toBe('2026-02-04')
            ->and($transaction->isSandbox)->toBeFalse()
            ->and($transaction->pretiumTransactionId)->toBe('pretium_abc');
    });

    it('creates transaction with mixed case keys (API compatibility)', function () {
        // Simulates an API response with mixed naming conventions
        $data = [
            'id' => 'txn_mixed',
            'user_id' => 'user_mixed',
            'amount' => 7500,
            'currency' => 'KES',
            'status' => 'failed',
            'recipient_phone_number' => '+256700000000',
            'reference_id' => 'ref_mixed',
            'failure_reason' => 'Insufficient funds',
            'created_at' => '2026-02-04T08:00:00+00:00',
            'updated_at' => '2026-02-04T08:30:00+00:00',
            'is_sandbox' => true,
        ];

        $transaction = Transaction::fromArray($data);

        expect($transaction->id)->toBe('txn_mixed')
            ->and($transaction->userId)->toBe('user_mixed')
            ->and($transaction->status)->toBe(TransactionStatus::FAILED)
            ->and($transaction->failureReason)->toBe('Insufficient funds')
            ->and($transaction->createdAt)->not->toBeNull()
            ->and($transaction->updatedAt)->not->toBeNull();
    });

    it('handles optional fields correctly', function () {
        $data = [
            'id' => 'txn_optional',
            'user_id' => 'user_opt',
            'amount' => 3000,
            'currency' => 'CDF',
            'status' => 'pending',
            'recipient_phone_number' => '+243970000000',
            'reference_id' => 'ref_opt',
            'created_at' => '2026-02-04T10:00:00+00:00',
            'updated_at' => '2026-02-04T10:00:00+00:00',
        ];

        $transaction = Transaction::fromArray($data);

        expect($transaction->metadata)->toBeNull()
            ->and($transaction->failureReason)->toBeNull()
            ->and($transaction->completedAt)->toBeNull()
            ->and($transaction->pretiumTransactionId)->toBeNull()
            ->and($transaction->error)->toBeNull()
            ->and($transaction->isSandbox)->toBeFalse()
            ->and($transaction->type)->toBe('deposit');
    });

    it('converts transaction to array correctly', function () {
        $data = [
            'id' => 'txn_toarray',
            'user_id' => 'user_arr',
            'amount' => 5000,
            'currency' => 'CDF',
            'status' => 'completed',
            'recipient_phone_number' => '+243970000000',
            'reference_id' => 'ref_arr',
            'completed_at' => '2026-02-04T12:00:00+00:00',
            'created_at' => '2026-02-04T10:00:00+00:00',
            'updated_at' => '2026-02-04T12:00:00+00:00',
            'is_sandbox' => true,
        ];

        $transaction = Transaction::fromArray($data);
        $array = $transaction->toArray();

        expect($array)->toBeArray()
            ->and($array['id'])->toBe('txn_toarray')
            ->and($array['userId'])->toBe('user_arr')
            ->and($array['status'])->toBe('completed')
            ->and($array['isSandbox'])->toBeTrue();
    });

    it('checks transaction status methods correctly', function () {
        $pendingTransaction = Transaction::fromArray([
            'id' => 'txn_pending',
            'user_id' => 'user_1',
            'amount' => 1000,
            'currency' => 'CDF',
            'status' => 'pending',
            'recipient_phone_number' => '+243970000000',
            'reference_id' => 'ref_1',
            'created_at' => '2026-02-04T10:00:00+00:00',
            'updated_at' => '2026-02-04T10:00:00+00:00',
        ]);

        expect($pendingTransaction->isPending())->toBeTrue()
            ->and($pendingTransaction->isCompleted())->toBeFalse()
            ->and($pendingTransaction->isFailed())->toBeFalse();

        $completedTransaction = Transaction::fromArray([
            'id' => 'txn_completed',
            'user_id' => 'user_2',
            'amount' => 2000,
            'currency' => 'CDF',
            'status' => 'completed',
            'recipient_phone_number' => '+243970000000',
            'reference_id' => 'ref_2',
            'created_at' => '2026-02-04T10:00:00+00:00',
            'updated_at' => '2026-02-04T10:00:00+00:00',
        ]);

        expect($completedTransaction->isPending())->toBeFalse()
            ->and($completedTransaction->isCompleted())->toBeTrue()
            ->and($completedTransaction->isFailed())->toBeFalse();

        $failedTransaction = Transaction::fromArray([
            'id' => 'txn_failed',
            'user_id' => 'user_3',
            'amount' => 3000,
            'currency' => 'CDF',
            'status' => 'failed',
            'recipient_phone_number' => '+243970000000',
            'reference_id' => 'ref_3',
            'failure_reason' => 'Transaction rejected',
            'created_at' => '2026-02-04T10:00:00+00:00',
            'updated_at' => '2026-02-04T10:00:00+00:00',
        ]);

        expect($failedTransaction->isPending())->toBeFalse()
            ->and($failedTransaction->isCompleted())->toBeFalse()
            ->and($failedTransaction->isFailed())->toBeTrue()
            ->and($failedTransaction->failureReason)->toBe('Transaction rejected');
    });

});
