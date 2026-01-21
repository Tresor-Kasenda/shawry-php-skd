<?php

declare(strict_types=1);

namespace Shwary\DTOs;

use JsonSerializable;
use Shwary\Enums\Country;
use Shwary\Exceptions\ValidationException;

final readonly class PaymentRequest implements JsonSerializable
{
    /**
     * @throws ValidationException
     */
    public function __construct(
        public int     $amount,
        public string  $clientPhoneNumber,
        public Country $country,
        public ?string $callbackUrl = null,
    ) {
        $this->validate();
    }

    /**
     * @throws ValidationException
     */
    private function validate(): void
    {
        // Validate amount
        $minimum = $this->country->getMinimumAmount();
        if ($this->amount <= $minimum) {
            throw ValidationException::invalidAmount($this->amount, $this->country);
        }

        // Validate phone number
        $dialCode = $this->country->getDialCode();
        if (!str_starts_with($this->clientPhoneNumber, $dialCode)) {
            throw ValidationException::invalidPhoneNumber($this->clientPhoneNumber, $this->country);
        }

        // Validate callback URL if provided
        if ($this->callbackUrl !== null && !$this->isValidHttpsUrl($this->callbackUrl)) {
            throw ValidationException::invalidCallbackUrl($this->callbackUrl);
        }
    }

    private function isValidHttpsUrl(string $url): bool
    {
        $parsed = parse_url($url);
        
        return isset($parsed['scheme'], $parsed['host'])
            && $parsed['scheme'] === 'https';
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'clientPhoneNumber' => $this->clientPhoneNumber,
        ];

        if ($this->callbackUrl !== null) {
            $data['callbackUrl'] = $this->callbackUrl;
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @throws ValidationException
     */
    public static function create(
        int $amount,
        string $phone,
        Country $country,
        ?string $callbackUrl = null
    ): self {
        return new self(
            amount: $amount,
            clientPhoneNumber: $phone,
            country: $country,
            callbackUrl: $callbackUrl,
        );
    }
}
