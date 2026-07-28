<?php

declare(strict_types=1);

namespace RideFareSystem\Services;

use RideFareSystem\Coupons\CouponInterface;
use RideFareSystem\Coupons\FirstRideFreeCoupon;
use RideFareSystem\Coupons\FlatDiscountCoupon;
use RideFareSystem\Coupons\PercentageDiscountCoupon;
use RideFareSystem\Exceptions\ValidationException;
use RideFareSystem\Logger;
use RideFareSystem\Repository\RideRepository;
use RideFareSystem\Ride;
use RideFareSystem\User;

class RideService
{
    private RideRepository $rideRepository;
    private FareCalculator $fareCalculator;
    private Logger $logger;

    public function __construct(
        RideRepository $rideRepository,
        FareCalculator $fareCalculator,
        Logger $logger
    ) {
        $this->rideRepository = $rideRepository;
        $this->fareCalculator = $fareCalculator;
        $this->logger = $logger;
    }

    public function resolveCoupon(string $code): ?CouponInterface
    {
        $code = strtoupper(trim($code));

        return match ($code) {
            '' => null,
            'FLAT5' => new FlatDiscountCoupon('FLAT5', 5.00),
            'PERCENT10' => new PercentageDiscountCoupon('PERCENT10', 10.0),
            'FIRSTRIDE' => new FirstRideFreeCoupon('FIRSTRIDE'),
            default => throw new ValidationException("Unknown coupon code: {$code}"),
        };
    }

    public function bookRide(
        User $user,
        string $rideType,
        float $distanceKm,
        int $durationMinutes,
        string $bookingTime,
        bool $isAirportRide,
        ?CouponInterface $coupon = null
    ): Ride {
        $username = $user->getUsername();
        $isFirstRide = $this->rideRepository->countByUsername($username) === 0;

        $breakdown = $this->fareCalculator->calculate(
            $rideType,
            $distanceKm,
            $durationMinutes,
            $bookingTime,
            $isAirportRide,
            $coupon,
            ['is_first_ride' => $isFirstRide]
        );

        $ride = new Ride(
            $username,
            $breakdown['ride_type'],
            $distanceKm,
            $durationMinutes,
            $bookingTime,
            $isAirportRide,
            $coupon?->getCode(),
            $breakdown
        );

        $this->rideRepository->save($ride);

        $this->logger->info('Fare Calculated', [
            'Username' => $username,
            'RideType' => $breakdown['ride_type'],
            'FinalFare' => '£' . number_format($breakdown['final_fare'], 2),
        ]);

        if ($coupon !== null) {
            $this->logger->info('Coupon Applied', [
                'Username' => $username,
                'Coupon' => $coupon->getCode(),
                'Discount' => '£' . number_format($breakdown['discount'], 2),
            ]);
        }

        $this->logger->info('Ride Completed', [
            'Username' => $username,
            'RideType' => $breakdown['ride_type'],
            'FinalFare' => '£' . number_format($breakdown['final_fare'], 2),
        ]);

        return $ride;
    }

    /**
     * @return Ride[]
     */
    public function getHistory(User $user): array
    {
        return $this->rideRepository->findByUsername($user->getUsername());
    }
}
