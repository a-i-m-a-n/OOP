<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

interface PricingStrategyInterface
{
    public function getRideTypeName(): string;

    public function getBaseFare(): float;

    public function getPerKmRate(): float;

    public function getPerMinuteRate(): float;

    public function isAirportEligible(): bool;

    public function calculateDistanceCost(float $distanceKm): float;

    public function calculateDurationCost(int $durationMinutes): float;
}
