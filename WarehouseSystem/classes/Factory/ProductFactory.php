<?php

declare(strict_types=1);

namespace WarehouseSystem\Factory;

use WarehouseSystem\DigitalProduct;
use WarehouseSystem\Exceptions\InvalidOrderException;
use WarehouseSystem\LimitedEditionProduct;
use WarehouseSystem\PhysicalProduct;
use WarehouseSystem\Product;

class ProductFactory
{
    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_DIGITAL = 'digital';
    public const TYPE_LIMITED_EDITION = 'limited_edition';

    public static function create(
        string $type,
        string $sku,
        string $name,
        int $totalStock,
        int $reservedStock = 0,
        int $soldStock = 0
    ): Product {
        return match (strtolower($type)) {
            self::TYPE_PHYSICAL => new PhysicalProduct($sku, $name, $totalStock, $reservedStock, $soldStock),
            self::TYPE_DIGITAL => new DigitalProduct($sku, $name, $totalStock, $reservedStock, $soldStock),
            self::TYPE_LIMITED_EDITION, 'limited' => new LimitedEditionProduct($sku, $name, $totalStock, $reservedStock, $soldStock),
            default => throw new InvalidOrderException("Unknown product type: {$type}"),
        };
    }

    public static function fromArray(array $row): Product
    {
        return self::create(
            $row['product_type'],
            $row['sku'],
            $row['name'],
            (int) $row['total_stock'],
            (int) ($row['reserved_stock'] ?? 0),
            (int) ($row['sold_stock'] ?? 0)
        );
    }
}
