<?php

declare(strict_types=1);

use Shwary\Config;
use Shwary\Shwary;
use Shwary\ShwaryClient;

describe('Shwary Facade', function () {

    afterEach(function () {
        Shwary::reset();
    });

    describe('initialization', function () {
        it('initializes with Config object', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            Shwary::init($config);

            expect(Shwary::client())->toBeInstanceOf(ShwaryClient::class);
        });

        it('initializes from array', function () {
            Shwary::initFromArray([
                'merchant_id' => 'array-merchant',
                'merchant_key' => 'array-key',
            ]);

            expect(Shwary::client())->toBeInstanceOf(ShwaryClient::class);
        });

        it('throws exception when not initialized', function () {
            Shwary::reset();
            Shwary::client();
        })->throws(RuntimeException::class, 'Shwary SDK not initialized');

        it('resets instance correctly', function () {
            $config = new Config(
                merchantId: 'test',
                merchantKey: 'key',
            );

            Shwary::init($config);
            expect(Shwary::client())->toBeInstanceOf(ShwaryClient::class);

            Shwary::reset();

            expect(fn() => Shwary::client())->toThrow(RuntimeException::class);
        });
    });

    describe('static method proxying', function () {
        it('proxies isSandbox method', function () {
            Shwary::initFromArray([
                'merchant_id' => 'proxy-merchant',
                'merchant_key' => 'proxy-key',
                'sandbox' => true,
            ]);

            expect(Shwary::isSandbox())->toBeTrue();
        });

        it('proxies getConfig method', function () {
            Shwary::initFromArray([
                'merchant_id' => 'proxy-merchant',
                'merchant_key' => 'proxy-key',
            ]);

            $config = Shwary::getConfig();

            expect($config)->toBeInstanceOf(Config::class)
                ->and($config->getMerchantId())->toBe('proxy-merchant');
        });
    });

});
