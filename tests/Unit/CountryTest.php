<?php

declare(strict_types=1);

use Shwary\Enums\Country;

describe('Country Enum', function () {

    describe('DRC', function () {
        it('has correct value', function () {
            expect(Country::DRC->value)->toBe('DRC');
        });

        it('returns CDF currency', function () {
            expect(Country::DRC->getCurrency())->toBe('CDF');
        });

        it('returns correct dial code', function () {
            expect(Country::DRC->getDialCode())->toBe('+243');
        });

        it('returns correct minimum amount', function () {
            expect(Country::DRC->getMinimumAmount())->toBe(2900);
        });

        it('returns correct country name', function () {
            expect(Country::DRC->getCountryName())->toBe('République Démocratique du Congo');
        });
    });

    describe('Kenya', function () {
        it('has correct value', function () {
            expect(Country::KENYA->value)->toBe('KE');
        });

        it('returns KES currency', function () {
            expect(Country::KENYA->getCurrency())->toBe('KES');
        });

        it('returns correct dial code', function () {
            expect(Country::KENYA->getDialCode())->toBe('+254');
        });

        it('returns correct minimum amount', function () {
            expect(Country::KENYA->getMinimumAmount())->toBe(0);
        });

        it('returns correct country name', function () {
            expect(Country::KENYA->getCountryName())->toBe('Kenya');
        });
    });

    describe('Uganda', function () {
        it('has correct value', function () {
            expect(Country::UGANDA->value)->toBe('UG');
        });

        it('returns UGX currency', function () {
            expect(Country::UGANDA->getCurrency())->toBe('UGX');
        });

        it('returns correct dial code', function () {
            expect(Country::UGANDA->getDialCode())->toBe('+256');
        });

        it('returns correct minimum amount', function () {
            expect(Country::UGANDA->getMinimumAmount())->toBe(0);
        });

        it('returns correct country name', function () {
            expect(Country::UGANDA->getCountryName())->toBe('Ouganda');
        });
    });

    it('can be created from string value', function () {
        expect(Country::from('DRC'))->toBe(Country::DRC)
            ->and(Country::from('KE'))->toBe(Country::KENYA)
            ->and(Country::from('UG'))->toBe(Country::UGANDA);
    });

    it('throws exception for invalid value', function () {
        Country::from('INVALID');
    })->throws(ValueError::class);

});
