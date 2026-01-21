<?php

declare(strict_types=1);

namespace Shwary\Webhook;

use Shwary\DTOs\Transaction;
use Shwary\Exceptions\ShwaryException;

final class WebhookHandler
{
    /**
     * Parses the payload of a Shwary webhook.
     *
     * @param string $payload The raw JSON content of the webhook
     * @return Transaction The parsed transaction
     * @throws ShwaryException If the payload is invalid
     */
    public function parsePayload(string $payload): Transaction
    {
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ShwaryException(
                message: 'Invalid webhook payload: ' . json_last_error_msg(),
                code: 400
            );
        }

        if (!isset($data['id'])) {
            throw new ShwaryException(
                message: 'Invalid webhook payload: missing transaction ID',
                code: 400
            );
        }

        return Transaction::fromArray($data);
    }

    /**
     * Parses the payload from PHP superglobals.
     *
     * @return Transaction
     * @throws ShwaryException
     */
    public function parseFromGlobals(): Transaction
    {
        $payload = file_get_contents('php://input');

        if ($payload === false || $payload === '') {
            throw new ShwaryException(
                message: 'Empty webhook payload',
                code: 400
            );
        }

        return $this->parsePayload($payload);
    }

    /**
     * Checks if the transaction is in a terminal state (completed or failed).
     */
    public function isTerminalStatus(Transaction $transaction): bool
    {
        return $transaction->isTerminal();
    }

    /**
     * Creates an appropriate HTTP response for Shwary.
     *
     * @param bool $success Indicates whether processing was successful
     * @param string $message Optional message
     * @return array Array to encode as JSON for the response
     */
    public function createResponse(bool $success, string $message = ''): array
    {
        return [
            'success' => $success,
            'message' => $message ?: ($success ? 'Webhook processed successfully' : 'Webhook processing failed'),
            'timestamp' => date('c'),
        ];
    }
}
