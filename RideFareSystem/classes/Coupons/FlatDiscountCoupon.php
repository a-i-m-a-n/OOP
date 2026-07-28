<?php

declare(strict_types=1);

namespace RideFareSystem\Coupons;

class FlatDiscountCoupon implements CouponInterface
{
    private string $code;
    private float $amount;

    public function __construct(string $code, float $amount)
    {
        $this->code = $code;
        $this->amount = $amount;
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
        return round(min($this->amount, $preDiscountTotal), 2);
    }
}
