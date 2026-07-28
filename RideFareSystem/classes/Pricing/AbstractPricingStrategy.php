<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

abstract class AbstractPricingStrategy implements PricingStrategyInterface
{
    public function calculateDistanceCost(float $distanceKm): float
    {
        return round($distanceKm * $this->getPerKmRate(), 2);
    }

    public function calculateDurationCost(int $durationMinutes): float
    {
        return round($durationMinutes * $this->getPerMinuteRate(), 2);
    }
}
