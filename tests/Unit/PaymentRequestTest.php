<?php

declare(strict_types=1);

use Shwary\DTOs\PaymentRequest;
use Shwary\Enums\Country;
use Shwary\Exceptions\ValidationException;

describe('PaymentRequest DTO', function () {

    describe('DRC payments', function () {
        it('creates valid DRC payment request', function () {
            $request = new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
            );

            expect($request->amount)->toBe(5000)
                ->and($request->clientPhoneNumber)->toBe('+243970000000')
                ->and($request->country)->toBe(Country::DRC)
                ->and($request->callbackUrl)->toBeNull();
        });

        it('creates DRC payment request with callback URL', function () {
            $request = new PaymentRequest(
                amount: 10000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
                callbackUrl: 'https://example.com/webhook',
            );

            expect($request->callbackUrl)->toBe('https://example.com/webhook');
        });

        it('throws exception for amount below minimum in DRC', function () {
            new PaymentRequest(
                amount: 2900,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
            );
        })->throws(ValidationException::class, 'Amount must be greater than 2900');

        it('throws exception for invalid DRC phone number', function () {
            new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+254700000000',
                country: Country::DRC,
            );
        })->throws(ValidationException::class, 'Phone must start with +243');
    });

    describe('Kenya payments', function () {
        it('creates valid Kenya payment request', function () {
            $request = new PaymentRequest(
                amount: 100,
                clientPhoneNumber: '+254700000000',
                country: Country::KENYA,
            );

            expect($request->amount)->toBe(100)
                ->and($request->clientPhoneNumber)->toBe('+254700000000')
                ->and($request->country)->toBe(Country::KENYA);
        });

        it('throws exception for invalid Kenya phone number', function () {
            new PaymentRequest(
                amount: 100,
                clientPhoneNumber: '+243970000000',
                country: Country::KENYA,
            );
        })->throws(ValidationException::class, 'Phone must start with +254');
    });

    describe('Uganda payments', function () {
        it('creates valid Uganda payment request', function () {
            $request = new PaymentRequest(
                amount: 500,
                clientPhoneNumber: '+256700000000',
                country: Country::UGANDA,
            );

            expect($request->amount)->toBe(500)
                ->and($request->clientPhoneNumber)->toBe('+256700000000')
                ->and($request->country)->toBe(Country::UGANDA);
        });

        it('throws exception for invalid Uganda phone number', function () {
            new PaymentRequest(
                amount: 500,
                clientPhoneNumber: '+243970000000',
                country: Country::UGANDA,
            );
        })->throws(ValidationException::class, 'Phone must start with +256');
    });

    describe('callback URL validation', function () {
        it('accepts valid HTTPS callback URL', function () {
            $request = new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
                callbackUrl: 'https://secure.example.com/webhook/payment',
            );

            expect($request->callbackUrl)->toBe('https://secure.example.com/webhook/payment');
        });

        it('rejects HTTP callback URL', function () {
            new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
                callbackUrl: 'http://example.com/webhook',
            );
        })->throws(ValidationException::class, 'Must be a valid HTTPS URL');

        it('rejects invalid callback URL', function () {
            new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
                callbackUrl: 'not-a-valid-url',
            );
        })->throws(ValidationException::class, 'Must be a valid HTTPS URL');
    });

    describe('serialization', function () {
        it('converts to array without callback URL', function () {
            $request = new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
            );

            $array = $request->toArray();

            expect($array)->toBeArray()
                ->and($array)->toHaveKey('amount', 5000)
                ->and($array)->toHaveKey('clientPhoneNumber', '+243970000000')
                ->and($array)->not->toHaveKey('callbackUrl');
        });

        it('converts to array with callback URL', function () {
            $request = new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
                callbackUrl: 'https://example.com/webhook',
            );

            $array = $request->toArray();

            expect($array)->toHaveKey('callbackUrl', 'https://example.com/webhook');
        });

        it('is JSON serializable', function () {
            $request = new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
            );

            $json = json_encode($request);

            expect($json)->toBeString()
                ->and(json_decode($json, true))->toBeArray()
                ->and(json_decode($json, true)['amount'])->toBe(5000);
        });
    });

    describe('factory method', function () {
        it('creates payment request using create method', function () {
            $request = PaymentRequest::create(
                amount: 5000,
                phone: '+243970000000',
                country: Country::DRC,
                callbackUrl: 'https://example.com/webhook',
            );

            expect($request)->toBeInstanceOf(PaymentRequest::class)
                ->and($request->amount)->toBe(5000)
                ->and($request->clientPhoneNumber)->toBe('+243970000000')
                ->and($request->country)->toBe(Country::DRC)
                ->and($request->callbackUrl)->toBe('https://example.com/webhook');
        });
    });

});
