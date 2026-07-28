<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

class EconomyPricing extends AbstractPricingStrategy
{
    public function getRideTypeName(): string
    {
        return 'Economy';
    }

    public function getBaseFare(): float
    {
        return 2.50;
    }

    public function getPerKmRate(): float
    {
        return 0.80;
    }

    public function getPerMinuteRate(): float
    {
        return 0.15;
    }

    public function isAirportEligible(): bool
    {
        return true;
    }
}
