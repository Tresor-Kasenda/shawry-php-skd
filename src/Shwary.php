<?php

declare(strict_types=1);

namespace Shwary;

use Psr\Log\LoggerInterface;
use Shwary\DTOs\PaymentRequest;
use Shwary\DTOs\Transaction;
use Shwary\Enums\Country;
use Shwary\Webhook\WebhookHandler;

/**
 * Facade for simplified usage of Shwary SDK.
 * 
 * @method static Transaction createPayment(PaymentRequest $request)
 * @method static Transaction pay(int $amount, string $phone, Country $country, ?string $callbackUrl = null)
 * @method static Transaction payDRC(int $amount, string $phone, ?string $callbackUrl = null)
 * @method static Transaction payKenya(int $amount, string $phone, ?string $callbackUrl = null)
 * @method static Transaction payUganda(int $amount, string $phone, ?string $callbackUrl = null)
 * @method static Transaction createSandboxPayment(PaymentRequest $request)
 * @method static Transaction sandboxPay(int $amount, string $phone, Country $country, ?string $callbackUrl = null)
 * @method static Transaction parseWebhook(string $payload)
 * @method static WebhookHandler webhook()
 * @method static bool isSandbox()
 * @method static Config getConfig()
 */
final class Shwary
{
    private static ?ShwaryClient $instance = null;

    /**
     * Prevents direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Initializes the SDK with the provided configuration.
     */
    public static function init(Config $config, ?LoggerInterface $logger = null): void
    {
        self::$instance = new ShwaryClient($config, null, $logger);
    }

    /**
     * Initializes the SDK from environment variables.
     */
    public static function initFromEnvironment(?LoggerInterface $logger = null): void
    {
        self::$instance = ShwaryClient::fromEnvironment($logger);
    }

    /**
     * Initializes the SDK from a configuration array.
     */
    public static function initFromArray(array $config, ?LoggerInterface $logger = null): void
    {
        self::$instance = ShwaryClient::fromArray($config, $logger);
    }

    /**
     * Returns the client instance.
     *
     * @throws \RuntimeException If the SDK is not initialized
     */
    public static function client(): ShwaryClient
    {
        if (self::$instance === null) {
            throw new \RuntimeException(
                'Shwary SDK not initialized. Call Shwary::init(), Shwary::initFromEnvironment(), or Shwary::initFromArray() first.'
            );
        }

        return self::$instance;
    }

    /**
     * Resets the instance (useful for tests).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Proxies method calls to the client instance.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return self::client()->$method(...$arguments);
    }
}
