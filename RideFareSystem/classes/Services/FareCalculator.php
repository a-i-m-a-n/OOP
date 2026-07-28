<?php

declare(strict_types=1);

namespace RideFareSystem\Services;

use RideFareSystem\Coupons\CouponInterface;
use RideFareSystem\Exceptions\ValidationException;
use RideFareSystem\Pricing\AirportFeeCalculator;
use RideFareSystem\Pricing\BikePricing;
use RideFareSystem\Pricing\EconomyPricing;
use RideFareSystem\Pricing\PremiumPricing;
use RideFareSystem\Pricing\PricingStrategyInterface;
use RideFareSystem\Pricing\SurgeCalculator;

class FareCalculator
{
    private SurgeCalculator $surgeCalculator;
    private AirportFeeCalculator $airportFeeCalculator;

    public function __construct(
        ?SurgeCalculator $surgeCalculator = null,
        ?AirportFeeCalculator $airportFeeCalculator = null
    ) {
        $this->surgeCalculator = $surgeCalculator ?? new SurgeCalculator();
        $this->airportFeeCalculator = $airportFeeCalculator ?? new AirportFeeCalculator();
    }

    /**
     * Maps a ride type string to its PricingStrategy implementation.
     * This is the one place a new ride type must be registered.
     */
    public function resolvePricingStrategy(string $rideType): PricingStrategyInterface
    {
        return match (strtolower($rideType)) {
            'economy' => new EconomyPricing(),
            'premium' => new PremiumPricing(),
            'bike' => new BikePricing(),
            default => throw new ValidationException("Unknown ride type: {$rideType}"),
        };
    }

    /**
     * @return array{
     *   ride_type: string,
     *   distance_km: float,
     *   duration_minutes: int,
     *   base_fare: float,
     *   distance_cost: float,
     *   duration_cost: float,
     *   subtotal: float,
     *   is_peak_hour: bool,
     *   surge_amount: float,
     *   airport_fee: float,
     *   pre_discount_total: float,
     *   coupon_code: ?string,
     *   discount: float,
     *   final_fare: float
     * }
     */
    public function calculate(
        string $rideType,
        float $distanceKm,
        int $durationMinutes,
        string $bookingTime,
        bool $isAirportRide,
        ?CouponInterface $coupon = null,
        array $couponContext = []
    ): array {
        if ($distanceKm <= 0) {
            throw new ValidationException('Distance must be greater than 0.');
        }
        if ($durationMinutes <= 0) {
            throw new ValidationException('Duration must be greater than 0.');
        }

        $strategy = $this->resolvePricingStrategy($rideType);

        if ($isAirportRide && !$strategy->isAirportEligible()) {
            throw new ValidationException(
                "{$strategy->getRideTypeName()} rides are not eligible for airport bookings."
            );
        }

        $baseFare = $strategy->getBaseFare();
        $distanceCost = $strategy->calculateDistanceCost($distanceKm);
        $durationCost = $strategy->calculateDurationCost($durationMinutes);
        $subtotal = round($baseFare + $distanceCost + $durationCost, 2);

        $isPeakHour = $this->surgeCalculator->isPeakHour($bookingTime);
        $surgeAmount = $this->surgeCalculator->calculateSurgeAmount($subtotal, $bookingTime);

        $airportFee = $this->airportFeeCalculator->calculate($strategy, $isAirportRide);

        $preDiscountTotal = round($subtotal + $surgeAmount + $airportFee, 2);

        $discount = 0.0;
        $couponCode = null;
        if ($coupon !== null) {
            $couponCode = $coupon->getCode();
            if ($coupon->isApplicable($couponContext)) {
                $discount = $coupon->calculateDiscount($preDiscountTotal, $couponContext);
                $discount = min($discount, $preDiscountTotal); // never over-discount
            }
        }

        $finalFare = round(max(0.0, $preDiscountTotal - $discount), 2);

        return [
            'ride_type' => $strategy->getRideTypeName(),
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'base_fare' => $baseFare,
            'distance_cost' => $distanceCost,
            'duration_cost' => $durationCost,
            'subtotal' => $subtotal,
            'is_peak_hour' => $isPeakHour,
            'surge_amount' => $surgeAmount,
            'airport_fee' => $airportFee,
            'pre_discount_total' => $preDiscountTotal,
            'coupon_code' => $couponCode,
            'discount' => round($discount, 2),
            'final_fare' => $finalFare,
        ];
    }
}
