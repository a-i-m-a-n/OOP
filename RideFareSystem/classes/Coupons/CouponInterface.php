<?php

declare(strict_types=1);

namespace RideFareSystem\Coupons;

interface CouponInterface
{
    public function getCode(): string;

    /**
     * @param float $preDiscountTotal The fare total before this discount is applied
     * @param array{is_first_ride?: bool} $context
     * @return float The discount amount (never negative, never more than $preDiscountTotal)
     */
    public function calculateDiscount(float $preDiscountTotal, array $context = []): float;

    /**
     * Whether this coupon is allowed to apply given the context
     * (e.g. FirstRideFreeCoupon requires is_first_ride === true).
     */
    public function isApplicable(array $context = []): bool;
}
