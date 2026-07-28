<?php

declare(strict_types=1);

namespace WarehouseSystem;

class DigitalProduct extends Product
{
    public function getProductType(): string
    {
        return 'digital';
    }

    public function getAvailableStock(): int
    {
        return PHP_INT_MAX;
    }

    public function reserve(int $quantity): void
    {
        // Still validate (quantity must be positive), but never touch stock.
        $this->validateOrderQuantity($quantity);
    }

    public function release(int $quantity): void
    {
        // Nothing was ever reserved, so there is nothing to release.
    }

    public function ship(int $quantity): void
    {
        $this->validateOrderQuantity($quantity);
        $this->soldStock += $quantity;
    }
}
