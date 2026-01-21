<?php

declare(strict_types=1);

namespace Shwary\Exceptions;

class AuthenticationException extends ShwaryException
{
    public static function invalidCredentials(): self
    {
        return new self(
            message: 'Invalid merchant credentials. Please verify your merchant ID and key.',
            code: 401
        );
    }

    public static function missingCredentials(): self
    {
        return new self(
            message: 'Missing merchant credentials. Both merchant ID and key are required.',
            code: 401
        );
    }
}
