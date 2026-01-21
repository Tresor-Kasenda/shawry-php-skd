<?php

declare(strict_types=1);

namespace Shwary;

use Psr\Log\LoggerInterface;
use Shwary\DTOs\PaymentRequest;
use Shwary\DTOs\Transaction;
use Shwary\Enums\Country;
use Shwary\Exceptions\ApiException;
use Shwary\Exceptions\AuthenticationException;
use Shwary\Exceptions\ShwaryException;
use Shwary\Http\HttpClient;
use Shwary\Webhook\WebhookHandler;

final class ShwaryClient
{
    private HttpClient $http;
    private WebhookHandler $webhookHandler;

    public function __construct(
        private readonly Config $config,
        ?HttpClient $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->http = $httpClient ?? new HttpClient($config, null, $logger);
        $this->webhookHandler = new WebhookHandler();
    }

    /**
     * Creates a client from environment variables.
     */
    public static function fromEnvironment(?LoggerInterface $logger = null): self
    {
        return new self(Config::fromEnvironment(), null, $logger);
    }

    /**
     * Creates a client from a configuration array.
     */
    public static function fromArray(array $config, ?LoggerInterface $logger = null): self
    {
        return new self(Config::fromArray($config), null, $logger);
    }

    /**
     * Initiates a Mobile Money payment.
     *
     * @param PaymentRequest $request The payment request
     * @return Transaction The created transaction
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function createPayment(PaymentRequest $request): Transaction
    {
        $endpoint = $this->getPaymentEndpoint($request->country);
        $response = $this->http->post($endpoint, $request->toArray());

        return Transaction::fromArray($response);
    }

    /**
     * Initiates a payment with direct parameters.
     *
     * @param int $amount Payment amount
     * @param string $phone Customer phone number (E.164 format)
     * @param Country $country Payment country
     * @param string|null $callbackUrl Optional callback URL
     * @return Transaction
     */
    public function pay(
        int $amount,
        string $phone,
        Country $country,
        ?string $callbackUrl = null
    ): Transaction {
        $request = PaymentRequest::create($amount, $phone, $country, $callbackUrl);

        try {
            return $this->createPayment($request);
        } catch (ApiException|AuthenticationException $e) {

        }
    }

    /**
     * Shortcut for a DRC (CDF) payment.
     */
    public function payDRC(int $amount, string $phone, ?string $callbackUrl = null): Transaction
    {
        return $this->pay($amount, $phone, Country::DRC, $callbackUrl);
    }

    /**
     * Shortcut for a Kenya (KES) payment.
     */
    public function payKenya(int $amount, string $phone, ?string $callbackUrl = null): Transaction
    {
        return $this->pay($amount, $phone, Country::KENYA, $callbackUrl);
    }

    /**
     * Shortcut for an Uganda (UGX) payment.
     */
    public function payUganda(int $amount, string $phone, ?string $callbackUrl = null): Transaction
    {
        return $this->pay($amount, $phone, Country::UGANDA, $callbackUrl);
    }

    /**
     * Initiates a sandbox (test) payment.
     *
     * @param PaymentRequest $request The payment request
     * @return Transaction The created test transaction
     */
    public function createSandboxPayment(PaymentRequest $request): Transaction
    {
        $endpoint = $this->getSandboxPaymentEndpoint($request->country);
        $response = $this->http->post($endpoint, $request->toArray());

        return Transaction::fromArray($response);
    }

    /**
     * Shortcut for a sandbox payment.
     */
    public function sandboxPay(
        int $amount,
        string $phone,
        Country $country,
        ?string $callbackUrl = null
    ): Transaction {
        $request = PaymentRequest::create($amount, $phone, $country, $callbackUrl);
        
        return $this->createSandboxPayment($request);
    }

    /**
     * Returns the webhook handler.
     */
    public function webhook(): WebhookHandler
    {
        return $this->webhookHandler;
    }

    /**
     * Parses a webhook payload.
     * @throws ShwaryException
     */
    public function parseWebhook(string $payload): Transaction
    {
        return $this->webhookHandler->parsePayload($payload);
    }

    /**
     * Checks if the client is in sandbox mode.
     */
    public function isSandbox(): bool
    {
        return $this->config->isSandbox();
    }

    /**
     * Returns the current configuration.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    private function getPaymentEndpoint(Country $country): string
    {
        if ($this->config->isSandbox()) {
            return $this->getSandboxPaymentEndpoint($country);
        }

        return sprintf('merchants/payment/%s', $country->value);
    }

    private function getSandboxPaymentEndpoint(Country $country): string
    {
        return sprintf('merchants/payment/sandbox/%s', $country->value);
    }
}
