<?php

declare(strict_types=1);

namespace WarehouseSystem;

use WarehouseSystem\Exceptions\LimitedEditionException;

class LimitedEditionProduct extends PhysicalProduct
{
    private const MAX_PER_ORDER = 1;

    public function getProductType(): string
    {
        return 'limited_edition';
    }

    public function validateOrderQuantity(int $quantity): void
    {
        parent::validateOrderQuantity($quantity);

        if ($quantity > self::MAX_PER_ORDER) {
            throw new LimitedEditionException(
                "{$this->name} is a limited-edition item: maximum "
                . self::MAX_PER_ORDER . ' per order (requested ' . $quantity . ').'
            );
        }
    }
}
