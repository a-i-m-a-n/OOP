<?php

declare(strict_types=1);

namespace RideFareSystem\Coupons;

class PercentageDiscountCoupon implements CouponInterface
{
    private string $code;
    private float $percentage; // e.g. 10 for 10%

    public function __construct(string $code, float $percentage)
    {
        $this->code = $code;
        $this->percentage = $percentage;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function isApplicable(array $context = []): bool
    {
        return true;
    }

    public function calculateDiscount(float $preDiscountTotal, array $context = []): float
    {
        return round($preDiscountTotal * ($this->percentage / 100), 2);
    }
}
