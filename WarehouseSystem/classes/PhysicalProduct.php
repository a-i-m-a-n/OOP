<?php

declare(strict_types=1);

namespace WarehouseSystem;

class PhysicalProduct extends Product
{
    public function getProductType(): string
    {
        return 'physical';
    }
}
