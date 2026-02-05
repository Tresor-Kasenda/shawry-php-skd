<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Shwary\Config;
use Shwary\Exceptions\ApiException;
use Shwary\Exceptions\AuthenticationException;
use Shwary\Http\HttpClient;

describe('HttpClient', function () {

    describe('successful requests', function () {
        it('makes POST request and returns response', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(200, [], json_encode([
                'id' => 'txn_123',
                'status' => 'pending',
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $result = $httpClient->post('test/endpoint', ['amount' => 5000]);

            expect($result)->toBeArray()
                ->and($result['id'])->toBe('txn_123')
                ->and($result['status'])->toBe('pending');
        });

        it('makes GET request and returns response', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(200, [], json_encode([
                'transactions' => [
                    ['id' => 'txn_1'],
                    ['id' => 'txn_2'],
                ],
            ]));

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $result = $httpClient->get('transactions', ['page' => 1]);

            expect($result)->toBeArray()
                ->and($result['transactions'])->toHaveCount(2);
        });

        it('handles empty response body', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(200, [], '');

            $mock = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);
            $result = $httpClient->post('test/endpoint');

            expect($result)->toBeArray()->toBeEmpty();
        });
    });

    describe('error handling', function () {
        it('throws AuthenticationException on 401 response', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(401, [], json_encode([
                'message' => 'Invalid credentials',
            ]));

            $mock = new MockHandler([
                new \GuzzleHttp\Exception\ClientException(
                    'Unauthorized',
                    new Request('POST', 'test'),
                    $mockResponse
                ),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);

            expect(fn() => $httpClient->post('test/endpoint'))
                ->toThrow(AuthenticationException::class);
        });

        it('throws ApiException on 400 response', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(400, [], json_encode([
                'message' => 'Bad request',
            ]));

            $mock = new MockHandler([
                new \GuzzleHttp\Exception\ClientException(
                    'Bad Request',
                    new Request('POST', 'test'),
                    $mockResponse
                ),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);

            expect(fn() => $httpClient->post('test/endpoint'))
                ->toThrow(ApiException::class, 'Bad request');
        });

        it('throws ApiException on 502 response', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(502, [], json_encode([
                'message' => 'Bad Gateway',
            ]));

            $mock = new MockHandler([
                new \GuzzleHttp\Exception\ServerException(
                    'Bad Gateway',
                    new Request('POST', 'test'),
                    $mockResponse
                ),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);

            expect(fn() => $httpClient->post('test/endpoint'))
                ->toThrow(ApiException::class, 'Payment gateway error');
        });

        it('throws ApiException on 500 response', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(500, [], json_encode([
                'message' => 'Internal Server Error',
            ]));

            $mock = new MockHandler([
                new \GuzzleHttp\Exception\ServerException(
                    'Server Error',
                    new Request('POST', 'test'),
                    $mockResponse
                ),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);

            expect(fn() => $httpClient->post('test/endpoint'))
                ->toThrow(ApiException::class);
        });

        it('throws ApiException on network error', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mock = new MockHandler([
                new ConnectException(
                    'Connection timeout',
                    new Request('POST', 'test')
                ),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);

            expect(fn() => $httpClient->post('test/endpoint'))
                ->toThrow(ApiException::class, 'Network error');
        });
    });

    describe('response parsing', function () {
        it('parses JSON response with error field', function () {
            $config = new Config(
                merchantId: 'test-merchant',
                merchantKey: 'test-key',
            );

            $mockResponse = new Response(400, [], json_encode([
                'error' => 'Validation failed',
            ]));

            $mock = new MockHandler([
                new \GuzzleHttp\Exception\ClientException(
                    'Bad Request',
                    new Request('POST', 'test'),
                    $mockResponse
                ),
            ]);
            $handlerStack = HandlerStack::create($mock);
            $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

            $httpClient = new HttpClient($config, $guzzleClient);

            expect(fn() => $httpClient->post('test/endpoint'))
                ->toThrow(ApiException::class, 'Validation failed');
        });
    });

});
