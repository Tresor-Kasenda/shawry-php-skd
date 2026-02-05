<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Shwary\Config;
use Shwary\DTOs\PaymentRequest;
use Shwary\Enums\Country;
use Shwary\Http\HttpClient;
use Shwary\ShwaryClient;

describe('ShwaryClient', function () {

    describe('factory methods', function () {
        it('creates client from array', function () {
            $client = ShwaryClient::fromArray([
                'merchant_id' => 'array-merchant',
                'merchant_key' => 'array-key',
            ]);

            expect($client)->toBeInstanceOf(ShwaryClient::class)
                ->and($client->getConfig()->getMerchantId())->toBe('array-merchant');
        });
    });

    describe('configuration', function () {
        it('returns config', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );
            $client = new ShwaryClient($config);

            expect($client->getConfig())->toBe($config);
        });

        it('returns sandbox status', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
                sandbox: false,
            );
            $sandboxConfig = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
                sandbox: true,
            );

            $client = new ShwaryClient($config);
            $sandboxClient = new ShwaryClient($sandboxConfig);

            expect($client->isSandbox())->toBeFalse()
                ->and($sandboxClient->isSandbox())->toBeTrue();
        });
    });

    describe('webhook', function () {
        it('returns webhook handler', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );
            $client = new ShwaryClient($config);

            expect($client->webhook())->toBeInstanceOf(\Shwary\Webhook\WebhookHandler::class);
        });

        it('parses webhook payload', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );
            $client = new ShwaryClient($config);

            $payload = json_encode([
                'id' => 'txn_webhook',
                'user_id' => 'user_1',
                'amount' => 5000,
                'currency' => 'CDF',
                'status' => 'completed',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_1',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]);

            $transaction = $client->parseWebhook($payload);

            expect($transaction->id)->toBe('txn_webhook')
                ->and($transaction->isCompleted())->toBeTrue();
        });
    });

    describe('payment creation with mocked HTTP', function () {
        it('creates payment and returns transaction', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
                sandbox: false,
            );

            $mockResponse = new Response(200, [], json_encode([
                'id' => 'txn_created',
                'user_id' => 'user_merchant',
                'amount' => 5000,
                'currency' => 'CDF',
                'type' => 'deposit',
                'status' => 'pending',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_new',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
                'is_sandbox' => false,
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $client = new ShwaryClient($config, $httpClient);

            $request = new PaymentRequest(
                amount: 5000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
            );

            $transaction = $client->createPayment($request);

            expect($transaction->id)->toBe('txn_created')
                ->and($transaction->amount)->toBe(5000)
                ->and($transaction->isPending())->toBeTrue();
        });

        it('creates sandbox payment', function () {
            $sandboxConfig = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
                sandbox: true,
            );

            $mockResponse = new Response(200, [], json_encode([
                'id' => 'txn_sandbox',
                'user_id' => 'user_test',
                'amount' => 3000,
                'currency' => 'CDF',
                'type' => 'deposit',
                'status' => 'pending',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_sandbox',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
                'is_sandbox' => true,
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($sandboxConfig, $guzzleClient);
            $client = new ShwaryClient($sandboxConfig, $httpClient);

            $request = new PaymentRequest(
                amount: 3000,
                clientPhoneNumber: '+243970000000',
                country: Country::DRC,
            );

            $transaction = $client->createSandboxPayment($request);

            expect($transaction->id)->toBe('txn_sandbox')
                ->and($transaction->isSandbox)->toBeTrue();
        });
    });

    describe('country-specific payment shortcuts', function () {
        it('payDRC creates DRC payment', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(200, [], json_encode([
                'id' => 'txn_shortcut',
                'user_id' => 'user_1',
                'amount' => 5000,
                'currency' => 'CDF',
                'type' => 'deposit',
                'status' => 'pending',
                'recipient_phone_number' => '+243970000000',
                'reference_id' => 'ref_1',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $client = new ShwaryClient($config, $httpClient);

            $transaction = $client->payDRC(5000, '+243970000000');

            expect($transaction)->toBeInstanceOf(\Shwary\DTOs\Transaction::class);
        });

        it('payKenya creates Kenya payment', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(200, [], json_encode([
                'id' => 'txn_kenya',
                'user_id' => 'user_1',
                'amount' => 1000,
                'currency' => 'KES',
                'type' => 'deposit',
                'status' => 'pending',
                'recipient_phone_number' => '+254700000000',
                'reference_id' => 'ref_ke',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $client = new ShwaryClient($config, $httpClient);

            $transaction = $client->payKenya(1000, '+254700000000');

            expect($transaction->currency)->toBe('KES');
        });

        it('payUganda creates Uganda payment', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(200, [], json_encode([
                'id' => 'txn_uganda',
                'user_id' => 'user_1',
                'amount' => 5000,
                'currency' => 'UGX',
                'type' => 'deposit',
                'status' => 'pending',
                'recipient_phone_number' => '+256700000000',
                'reference_id' => 'ref_ug',
                'created_at' => '2026-02-05T10:00:00+00:00',
                'updated_at' => '2026-02-05T10:00:00+00:00',
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $client = new ShwaryClient($config, $httpClient);

            $transaction = $client->payUganda(5000, '+256700000000');

            expect($transaction->currency)->toBe('UGX');
        });
    });

});
