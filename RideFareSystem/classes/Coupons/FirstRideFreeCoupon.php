<?php

declare(strict_types=1);

namespace RideFareSystem\Coupons;

class FirstRideFreeCoupon implements CouponInterface
{
    private const MAX_DISCOUNT = 10.00;

    private string $code;

    public function __construct(string $code = 'FIRSTRIDE')
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function isApplicable(array $context = []): bool
    {
        return ($context['is_first_ride'] ?? false) === true;
    }

    public function calculateDiscount(float $preDiscountTotal, array $context = []): float
    {
        if (!$this->isApplicable($context)) {
            return 0.0;
        }

        return round(min($preDiscountTotal, self::MAX_DISCOUNT), 2);
    }
}
