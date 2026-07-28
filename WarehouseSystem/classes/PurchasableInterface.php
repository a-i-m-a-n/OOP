<?php

declare(strict_types=1);

namespace WarehouseSystem;

interface PurchasableInterface
{
    public function getSku(): string;

    public function getName(): string;

    public function getProductType(): string;
}
