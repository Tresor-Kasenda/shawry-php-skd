<?php

declare(strict_types=1);

namespace Shwary\Enums;

enum Country: string
{
    case DRC = 'DRC';
    case KENYA = 'KE';
    case UGANDA = 'UG';

    public function getCurrency(): string
    {
        return match ($this) {
            self::DRC => 'CDF',
            self::KENYA => 'KES',
            self::UGANDA => 'UGX',
        };
    }

    public function getDialCode(): string
    {
        return match ($this) {
            self::DRC => '+243',
            self::KENYA => '+254',
            self::UGANDA => '+256',
        };
    }

    public function getMinimumAmount(): int
    {
        return match ($this) {
            self::DRC => 2900,
            self::KENYA => 0,
            self::UGANDA => 0,
        };
    }

    public function getCountryName(): string
    {
        return match ($this) {
            self::DRC => 'République Démocratique du Congo',
            self::KENYA => 'Kenya',
            self::UGANDA => 'Ouganda',
        };
    }
}
