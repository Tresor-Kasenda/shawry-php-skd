<?php

declare(strict_types=1);

namespace Shwary\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::PENDING => false,
            self::COMPLETED, self::FAILED => true,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }
}
