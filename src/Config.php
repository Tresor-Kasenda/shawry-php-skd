<?php

declare(strict_types=1);

namespace Shwary;

use InvalidArgumentException;

final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.shwary.com';
    public const DEFAULT_TIMEOUT = 30;
    public const API_VERSION = 'v1';

    public function __construct(
        private readonly string $merchantId,
        private readonly string $merchantKey,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
        private readonly bool $sandbox = false,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->merchantId)) {
            throw new InvalidArgumentException('Merchant ID is required');
        }

        if (empty($this->merchantKey)) {
            throw new InvalidArgumentException('Merchant Key is required');
        }

        if ($this->timeout < 1) {
            throw new InvalidArgumentException('Timeout must be at least 1 second');
        }
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getMerchantKey(): string
    {
        return $this->merchantKey;
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function getApiUrl(): string
    {
        return sprintf('%s/api/%s', $this->getBaseUrl(), self::API_VERSION);
    }

    public static function fromArray(array $config): self
    {
        return new self(
            merchantId: $config['merchant_id'] ?? '',
            merchantKey: $config['merchant_key'] ?? '',
            baseUrl: $config['base_url'] ?? self::DEFAULT_BASE_URL,
            timeout: $config['timeout'] ?? self::DEFAULT_TIMEOUT,
            sandbox: $config['sandbox'] ?? false,
        );
    }

    public static function fromEnvironment(): self
    {
        return new self(
            merchantId: getenv('SHWARY_MERCHANT_ID') ?: '',
            merchantKey: getenv('SHWARY_MERCHANT_KEY') ?: '',
            baseUrl: getenv('SHWARY_BASE_URL') ?: self::DEFAULT_BASE_URL,
            timeout: (int) (getenv('SHWARY_TIMEOUT') ?: self::DEFAULT_TIMEOUT),
            sandbox: filter_var(getenv('SHWARY_SANDBOX'), FILTER_VALIDATE_BOOLEAN),
        );
    }
}
