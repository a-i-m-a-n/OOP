<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

class AirportFeeCalculator
{
    private const AIRPORT_FEE = 5.00;

    public function calculate(PricingStrategyInterface $pricingStrategy, bool $isAirportRide): float
    {
        if (!$isAirportRide) {
            return 0.0;
        }

        if (!$pricingStrategy->isAirportEligible()) {
            return 0.0;
        }

        return self::AIRPORT_FEE;
    }
}
