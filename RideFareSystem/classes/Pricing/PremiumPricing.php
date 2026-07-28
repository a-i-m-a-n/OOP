<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

class PremiumPricing extends AbstractPricingStrategy
{
    public function getRideTypeName(): string
    {
        return 'Premium';
    }

    public function getBaseFare(): float
    {
        return 5.00;
    }

    public function getPerKmRate(): float
    {
        return 1.20;
    }

    public function getPerMinuteRate(): float
    {
        return 0.25;
    }

    public function isAirportEligible(): bool
    {
        return true;
    }
}
