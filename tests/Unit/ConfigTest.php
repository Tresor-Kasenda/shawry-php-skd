<?php

declare(strict_types=1);

use Shwary\Config;

describe('Config', function () {

    it('creates config with required parameters', function () {
        $config = new Config(
            merchantId: 'test-merchant-id',
            merchantKey: 'test-merchant-key',
        );

        expect($config->getMerchantId())->toBe('test-merchant-id')
            ->and($config->getMerchantKey())->toBe('test-merchant-key')
            ->and($config->getBaseUrl())->toBe('https://api.shwary.com')
            ->and($config->getTimeout())->toBe(30)
            ->and($config->isSandbox())->toBeFalse();
    });

    it('creates config with all parameters', function () {
        $config = new Config(
            merchantId: 'merchant-123',
            merchantKey: 'secret-key',
            baseUrl: 'https://custom.api.com/',
            timeout: 60,
            sandbox: true,
        );

        expect($config->getMerchantId())->toBe('merchant-123')
            ->and($config->getMerchantKey())->toBe('secret-key')
            ->and($config->getBaseUrl())->toBe('https://custom.api.com')
            ->and($config->getTimeout())->toBe(60)
            ->and($config->isSandbox())->toBeTrue();
    });

    it('trims trailing slash from base URL', function () {
        $config = new Config(
            merchantId: 'merchant',
            merchantKey: 'key',
            baseUrl: 'https://api.example.com/',
        );

        expect($config->getBaseUrl())->toBe('https://api.example.com');
    });

    it('generates correct API URL', function () {
        $config = new Config(
            merchantId: 'merchant',
            merchantKey: 'key',
            baseUrl: 'https://api.shwary.com',
        );

        expect($config->getApiUrl())->toBe('https://api.shwary.com/api/v1');
    });

    it('throws exception for empty merchant ID', function () {
        new Config(
            merchantId: '',
            merchantKey: 'valid-key',
        );
    })->throws(InvalidArgumentException::class, 'Merchant ID is required');

    it('throws exception for empty merchant key', function () {
        new Config(
            merchantId: 'valid-id',
            merchantKey: '',
        );
    })->throws(InvalidArgumentException::class, 'Merchant Key is required');

    it('throws exception for invalid timeout', function () {
        new Config(
            merchantId: 'valid-id',
            merchantKey: 'valid-key',
            timeout: 0,
        );
    })->throws(InvalidArgumentException::class, 'Timeout must be at least 1 second');

    it('creates config from array', function () {
        $config = Config::fromArray([
            'merchant_id' => 'array-merchant-id',
            'merchant_key' => 'array-merchant-key',
            'base_url' => 'https://custom.api.com',
            'timeout' => 45,
            'sandbox' => true,
        ]);

        expect($config->getMerchantId())->toBe('array-merchant-id')
            ->and($config->getMerchantKey())->toBe('array-merchant-key')
            ->and($config->getBaseUrl())->toBe('https://custom.api.com')
            ->and($config->getTimeout())->toBe(45)
            ->and($config->isSandbox())->toBeTrue();
    });

    it('uses default values when creating from array with minimal data', function () {
        $config = Config::fromArray([
            'merchant_id' => 'merchant',
            'merchant_key' => 'key',
        ]);

        expect($config->getBaseUrl())->toBe('https://api.shwary.com')
            ->and($config->getTimeout())->toBe(30)
            ->and($config->isSandbox())->toBeFalse();
    });

});
