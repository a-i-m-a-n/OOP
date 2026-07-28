<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

class BikePricing extends AbstractPricingStrategy
{
    public function getRideTypeName(): string
    {
        return 'Bike';
    }

    public function getBaseFare(): float
    {
        return 1.00;
    }

    public function getPerKmRate(): float
    {
        return 0.40;
    }

    public function getPerMinuteRate(): float
    {
        return 0.05;
    }

    public function isAirportEligible(): bool
    {
        // Bike rides can never be airport rides.
        return false;
    }
}
