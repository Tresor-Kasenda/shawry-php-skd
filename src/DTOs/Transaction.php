<?php

declare(strict_types=1);

namespace Shwary\DTOs;

use DateTimeImmutable;
use Exception;
use JsonSerializable;
use Shwary\Enums\TransactionStatus;

final readonly class Transaction implements JsonSerializable
{
    public function __construct(
        public string             $id,
        public string             $userId,
        public int                $amount,
        public string             $currency,
        public string             $type,
        public TransactionStatus  $status,
        public string             $recipientPhoneNumber,
        public string             $referenceId,
        public ?array             $metadata,
        public ?string            $failureReason,
        public ?DateTimeImmutable $completedAt,
        public DateTimeImmutable  $createdAt,
        public DateTimeImmutable  $updatedAt,
        public bool               $isSandbox,
        public ?string            $pretiumTransactionId = null,
        public ?string            $error = null,
    ) {}

    /**
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['userId'],
            amount: (int) $data['amount'],
            currency: $data['currency'],
            type: $data['type'] ?? 'deposit',
            status: TransactionStatus::from($data['status']),
            recipientPhoneNumber: $data['recipientPhoneNumber'],
            referenceId: $data['referenceId'],
            metadata: $data['metadata'] ?? null,
            failureReason: $data['failureReason'] ?? null,
            completedAt: isset($data['completedAt']) 
                ? new DateTimeImmutable($data['completedAt']) 
                : null,
            createdAt: new DateTimeImmutable($data['createdAt']),
            updatedAt: new DateTimeImmutable($data['updatedAt']),
            isSandbox: (bool) ($data['isSandbox'] ?? false),
            pretiumTransactionId: $data['pretium_transaction_id'] ?? null,
            error: $data['error'] ?? null,
        );
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'type' => $this->type,
            'status' => $this->status->value,
            'recipientPhoneNumber' => $this->recipientPhoneNumber,
            'referenceId' => $this->referenceId,
            'metadata' => $this->metadata,
            'failureReason' => $this->failureReason,
            'completedAt' => $this->completedAt?->format('c'),
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
            'isSandbox' => $this->isSandbox,
            'pretiumTransactionId' => $this->pretiumTransactionId,
            'error' => $this->error,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
