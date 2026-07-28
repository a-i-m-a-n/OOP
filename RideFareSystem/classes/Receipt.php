<?php

declare(strict_types=1);

namespace RideFareSystem;

class Receipt
{
    private Ride $ride;

    public function __construct(Ride $ride)
    {
        $this->ride = $ride;
    }

    public function render(): string
    {
        $b = $this->ride->getFareBreakdown();
        $money = fn (float $v) => 'Rs' . number_format($v, 2);

        $lines = [];
        $lines[] = str_repeat('=', 40);
        $lines[] = str_pad(' RIDE RECEIPT', 40, ' ', STR_PAD_BOTH);
        $lines[] = str_repeat('=', 40);
        $lines[] = "Rider:            {$this->ride->getUsername()}";
        $lines[] = "Ride Type:        {$b['ride_type']}";
        $lines[] = "Distance:         {$this->ride->getDistanceKm()} km";
        $lines[] = "Duration:         {$this->ride->getDurationMinutes()} min";
        $lines[] = "Booking Time:     {$this->ride->getBookingTime()}";
        $lines[] = 'Airport Ride:     ' . ($this->ride->isAirportRide() ? 'Yes' : 'No');
        $lines[] = str_repeat('-', 40);
        $lines[] = 'Base Fare:        ' . $money($b['base_fare']);
        $lines[] = 'Distance Cost:    ' . $money($b['distance_cost']);
        $lines[] = 'Duration Cost:    ' . $money($b['duration_cost']);
        $lines[] = 'Subtotal:         ' . $money($b['subtotal']);
        $lines[] = 'Peak Hour Surge:  ' . $money($b['surge_amount']) . ($b['is_peak_hour'] ? ' (surge active)' : '');
        $lines[] = 'Airport Fee:      ' . $money($b['airport_fee']);

        if (!empty($b['coupon_code'])) {
            $lines[] = "Coupon Applied:   {$b['coupon_code']}";
        }
        $lines[] = 'Discount:        -' . $money($b['discount']);
        $lines[] = str_repeat('-', 40);
        $lines[] = 'FINAL FARE:       ' . $money($b['final_fare']);
        $lines[] = str_repeat('=', 40);

        return implode(PHP_EOL, $lines);
    }

    public function print(): void
    {
        echo $this->render() . PHP_EOL;
    }
}
