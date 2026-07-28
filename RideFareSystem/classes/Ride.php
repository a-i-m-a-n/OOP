<?php

declare(strict_types=1);

namespace RideFareSystem;

class Ride
{
    private string $username;
    private string $rideType;
    private float $distanceKm;
    private int $durationMinutes;
    private string $bookingTime; // HH:MM
    private bool $airportRide;
    private ?string $couponCode;
    private array $fareBreakdown; // set after calculation
    private string $bookedAt;

    public function __construct(
        string $username,
        string $rideType,
        float $distanceKm,
        int $durationMinutes,
        string $bookingTime,
        bool $airportRide,
        ?string $couponCode = null,
        array $fareBreakdown = [],
        ?string $bookedAt = null
    ) {
        $this->username = $username;
        $this->rideType = $rideType;
        $this->distanceKm = $distanceKm;
        $this->durationMinutes = $durationMinutes;
        $this->bookingTime = $bookingTime;
        $this->airportRide = $airportRide;
        $this->couponCode = $couponCode;
        $this->fareBreakdown = $fareBreakdown;
        $this->bookedAt = $bookedAt ?? date('Y-m-d H:i:s');
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRideType(): string
    {
        return $this->rideType;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getBookingTime(): string
    {
        return $this->bookingTime;
    }

    public function isAirportRide(): bool
    {
        return $this->airportRide;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function getFareBreakdown(): array
    {
        return $this->fareBreakdown;
    }

    public function setFareBreakdown(array $fareBreakdown): void
    {
        $this->fareBreakdown = $fareBreakdown;
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'ride_type' => $this->rideType,
            'distance_km' => $this->distanceKm,
            'duration_minutes' => $this->durationMinutes,
            'booking_time' => $this->bookingTime,
            'airport_ride' => $this->airportRide,
            'coupon_code' => $this->couponCode,
            'fare_breakdown' => $this->fareBreakdown,
            'booked_at' => $this->bookedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'],
            $data['ride_type'],
            (float) $data['distance_km'],
            (int) $data['duration_minutes'],
            $data['booking_time'],
            (bool) $data['airport_ride'],
            $data['coupon_code'] ?? null,
            $data['fare_breakdown'] ?? [],
            $data['booked_at'] ?? null
        );
    }
}
