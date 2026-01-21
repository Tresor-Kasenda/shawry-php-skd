<?php

declare(strict_types=1);

namespace Shwary\Exceptions;

use Shwary\Enums\Country;

class ValidationException extends ShwaryException
{
    public static function invalidAmount(int $amount, Country $country): self
    {
        $minimum = $country->getMinimumAmount();
        
        return new self(
            message: sprintf(
                'Invalid amount: %d %s. Amount must be greater than %d for %s.',
                $amount,
                $country->getCurrency(),
                $minimum,
                $country->getCountryName()
            ),
            code: 400,
            context: [
                'amount' => $amount,
                'minimum' => $minimum,
                'currency' => $country->getCurrency(),
                'country' => $country->value,
            ]
        );
    }

    public static function invalidPhoneNumber(string $phone, Country $country): self
    {
        return new self(
            message: sprintf(
                'Invalid phone number: %s. Phone must start with %s for %s.',
                $phone,
                $country->getDialCode(),
                $country->getCountryName()
            ),
            code: 400,
            context: [
                'phone' => $phone,
                'expected_prefix' => $country->getDialCode(),
                'country' => $country->value,
            ]
        );
    }

    public static function invalidCallbackUrl(string $url): self
    {
        return new self(
            message: sprintf('Invalid callback URL: %s. Must be a valid HTTPS URL.', $url),
            code: 400,
            context: ['url' => $url]
        );
    }

    public static function missingRequiredField(string $field): self
    {
        return new self(
            message: sprintf('Missing required field: %s', $field),
            code: 400,
            context: ['field' => $field]
        );
    }
}
