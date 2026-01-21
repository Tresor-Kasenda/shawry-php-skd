<?php

declare(strict_types=1);

namespace Shwary\Exceptions;

use Psr\Http\Message\ResponseInterface;

class ApiException extends ShwaryException
{
    private ?ResponseInterface $response = null;

    public static function fromResponse(ResponseInterface $response): self
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true) ?? [];
        
        $message = $data['message'] ?? $data['error'] ?? 'Unknown API error';
        
        $exception = new self(
            message: $message,
            code: $response->getStatusCode(),
            context: $data
        );
        
        $exception->response = $response;
        
        return $exception;
    }

    public static function networkError(string $message, ?\Throwable $previous = null): self
    {
        return new self(
            message: sprintf('Network error: %s', $message),
            code: 0,
            previous: $previous
        );
    }

    public static function badGateway(string $message = ''): self
    {
        return new self(
            message: $message ?: 'Payment gateway error. Please try again later.',
            code: 502
        );
    }

    public static function clientNotFound(string $phone): self
    {
        return new self(
            message: sprintf('Client with phone %s not found in Shwary system.', $phone),
            code: 404,
            context: ['phone' => $phone]
        );
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
