<?php

declare(strict_types=1);

namespace RideFareSystem\Pricing;

class SurgeCalculator
{
    private const SURGE_MULTIPLIER = 1.5;

    /** @var array<int, array{0:int,1:int}> Peak windows as [startHour, endHour) */
    private array $peakWindows = [
        [7, 10],
        [17, 20],
    ];

    /**
     * @param string $bookingTime "HH:MM" 24-hour format
     */
    public function isPeakHour(string $bookingTime): bool
    {
        $hour = (int) explode(':', $bookingTime)[0];

        foreach ($this->peakWindows as [$start, $end]) {
            if ($hour >= $start && $hour < $end) {
                return true;
            }
        }

        return false;
    }

    public function calculateSurgeAmount(float $subtotal, string $bookingTime): float
    {
        if (!$this->isPeakHour($bookingTime)) {
            return 0.0;
        }

        return round($subtotal * (self::SURGE_MULTIPLIER - 1), 2);
    }

    public function getMultiplier(): float
    {
        return self::SURGE_MULTIPLIER;
    }
}
