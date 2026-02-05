<?php

declare(strict_types=1);

use Shwary\Enums\Country;
use Shwary\Exceptions\ApiException;
use Shwary\Exceptions\AuthenticationException;
use Shwary\Exceptions\ShwaryException;
use Shwary\Exceptions\ValidationException;

describe('Exceptions', function () {

    describe('ShwaryException', function () {
        it('creates exception with message and code', function () {
            $exception = new ShwaryException('Test error', 500);

            expect($exception->getMessage())->toBe('Test error')
                ->and($exception->getCode())->toBe(500)
                ->and($exception->getContext())->toBeEmpty();
        });

        it('creates exception with context', function () {
            $exception = new ShwaryException(
                message: 'Error with context',
                code: 400,
                context: ['key' => 'value']
            );

            expect($exception->getContext())->toBe(['key' => 'value']);
        });

        it('converts to array', function () {
            $exception = new ShwaryException(
                message: 'Array test',
                code: 422,
                context: ['field' => 'amount']
            );

            $array = $exception->toArray();

            expect($array)->toBeArray()
                ->and($array['message'])->toBe('Array test')
                ->and($array['code'])->toBe(422)
                ->and($array['context'])->toBe(['field' => 'amount']);
        });
    });

    describe('ValidationException', function () {
        it('creates invalid amount exception for DRC', function () {
            $exception = ValidationException::invalidAmount(1000, Country::DRC);

            expect($exception->getMessage())->toContain('1000')
                ->and($exception->getMessage())->toContain('CDF')
                ->and($exception->getMessage())->toContain('2900')
                ->and($exception->getCode())->toBe(400)
                ->and($exception->getContext())->toHaveKey('amount', 1000)
                ->and($exception->getContext())->toHaveKey('minimum', 2900)
                ->and($exception->getContext())->toHaveKey('currency', 'CDF');
        });

        it('creates invalid phone number exception', function () {
            $exception = ValidationException::invalidPhoneNumber('+254700000000', Country::DRC);

            expect($exception->getMessage())->toContain('+254700000000')
                ->and($exception->getMessage())->toContain('+243')
                ->and($exception->getCode())->toBe(400)
                ->and($exception->getContext())->toHaveKey('phone', '+254700000000')
                ->and($exception->getContext())->toHaveKey('expected_prefix', '+243');
        });

        it('creates invalid callback URL exception', function () {
            $exception = ValidationException::invalidCallbackUrl('http://insecure.com');

            expect($exception->getMessage())->toContain('http://insecure.com')
                ->and($exception->getMessage())->toContain('HTTPS')
                ->and($exception->getCode())->toBe(400)
                ->and($exception->getContext())->toHaveKey('url', 'http://insecure.com');
        });

        it('creates missing required field exception', function () {
            $exception = ValidationException::missingRequiredField('amount');

            expect($exception->getMessage())->toContain('amount')
                ->and($exception->getCode())->toBe(400)
                ->and($exception->getContext())->toHaveKey('field', 'amount');
        });
    });

    describe('AuthenticationException', function () {
        it('creates invalid credentials exception', function () {
            $exception = AuthenticationException::invalidCredentials();

            expect($exception->getMessage())->toContain('Invalid merchant credentials')
                ->and($exception->getCode())->toBe(401);
        });

        it('creates missing credentials exception', function () {
            $exception = AuthenticationException::missingCredentials();

            expect($exception->getMessage())->toContain('Missing merchant credentials')
                ->and($exception->getCode())->toBe(401);
        });
    });

    describe('ApiException', function () {
        it('creates network error exception', function () {
            $exception = ApiException::networkError('Connection timeout');

            expect($exception->getMessage())->toContain('Network error')
                ->and($exception->getMessage())->toContain('Connection timeout')
                ->and($exception->getCode())->toBe(0);
        });

        it('creates network error exception with previous exception', function () {
            $previous = new Exception('Original error');
            $exception = ApiException::networkError('Connection failed', $previous);

            expect($exception->getPrevious())->toBe($previous);
        });

        it('creates bad gateway exception with default message', function () {
            $exception = ApiException::badGateway();

            expect($exception->getMessage())->toContain('Payment gateway error')
                ->and($exception->getCode())->toBe(502);
        });

        it('creates bad gateway exception with custom message', function () {
            $exception = ApiException::badGateway('Provider unavailable');

            expect($exception->getMessage())->toBe('Provider unavailable')
                ->and($exception->getCode())->toBe(502);
        });

        it('creates client not found exception', function () {
            $exception = ApiException::clientNotFound('+243970000000');

            expect($exception->getMessage())->toContain('+243970000000')
                ->and($exception->getMessage())->toContain('not found')
                ->and($exception->getCode())->toBe(404)
                ->and($exception->getContext())->toHaveKey('phone', '+243970000000');
        });
    });

    describe('Exception inheritance', function () {
        it('ValidationException extends ShwaryException', function () {
            $exception = ValidationException::missingRequiredField('test');

            expect($exception)->toBeInstanceOf(ShwaryException::class)
                ->and($exception)->toBeInstanceOf(Exception::class);
        });

        it('AuthenticationException extends ShwaryException', function () {
            $exception = AuthenticationException::invalidCredentials();

            expect($exception)->toBeInstanceOf(ShwaryException::class);
        });

        it('ApiException extends ShwaryException', function () {
            $exception = ApiException::networkError('test');

            expect($exception)->toBeInstanceOf(ShwaryException::class);
        });
    });

});
