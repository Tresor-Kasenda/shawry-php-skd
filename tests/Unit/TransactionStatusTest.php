<?php

declare(strict_types=1);

use Shwary\Enums\TransactionStatus;

describe('TransactionStatus Enum', function () {

    describe('PENDING', function () {
        it('has correct value', function () {
            expect(TransactionStatus::PENDING->value)->toBe('pending');
        });

        it('is not terminal', function () {
            expect(TransactionStatus::PENDING->isTerminal())->toBeFalse();
        });

        it('is not successful', function () {
            expect(TransactionStatus::PENDING->isSuccessful())->toBeFalse();
        });
    });

    describe('COMPLETED', function () {
        it('has correct value', function () {
            expect(TransactionStatus::COMPLETED->value)->toBe('completed');
        });

        it('is terminal', function () {
            expect(TransactionStatus::COMPLETED->isTerminal())->toBeTrue();
        });

        it('is successful', function () {
            expect(TransactionStatus::COMPLETED->isSuccessful())->toBeTrue();
        });
    });

    describe('FAILED', function () {
        it('has correct value', function () {
            expect(TransactionStatus::FAILED->value)->toBe('failed');
        });

        it('is terminal', function () {
            expect(TransactionStatus::FAILED->isTerminal())->toBeTrue();
        });

        it('is not successful', function () {
            expect(TransactionStatus::FAILED->isSuccessful())->toBeFalse();
        });
    });

    it('can be created from string value', function () {
        expect(TransactionStatus::from('pending'))->toBe(TransactionStatus::PENDING)
            ->and(TransactionStatus::from('completed'))->toBe(TransactionStatus::COMPLETED)
            ->and(TransactionStatus::from('failed'))->toBe(TransactionStatus::FAILED);
    });

    it('throws exception for invalid value', function () {
        TransactionStatus::from('invalid');
    })->throws(ValueError::class);

});
