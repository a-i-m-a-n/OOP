<?php

declare(strict_types=1);

namespace WarehouseSystem;

use WarehouseSystem\Exceptions\InsufficientStockException;
use WarehouseSystem\Exceptions\InvalidOrderException;

abstract class Product implements PurchasableInterface
{
    protected string $sku;
    protected string $name;
    protected int $totalStock;
    protected int $reservedStock;
    protected int $soldStock;

    public function __construct(
        string $sku,
        string $name,
        int $totalStock,
        int $reservedStock = 0,
        int $soldStock = 0
    ) {
        $this->sku = $sku;
        $this->name = $name;
        $this->totalStock = $totalStock;
        $this->reservedStock = $reservedStock;
        $this->soldStock = $soldStock;
    }

    abstract public function getProductType(): string;

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTotalStock(): int
    {
        return $this->totalStock;
    }

    public function getReservedStock(): int
    {
        return $this->reservedStock;
    }

    public function getSoldStock(): int
    {
        return $this->soldStock;
    }

    public function getAvailableStock(): int
    {
        return max(0, $this->totalStock - $this->reservedStock - $this->soldStock);
    }

    public function validateOrderQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidOrderException("Quantity for {$this->name} must be greater than 0.");
        }
    }

    public function reserve(int $quantity): void
    {
        $this->validateOrderQuantity($quantity);

        if ($quantity > $this->getAvailableStock()) {
            throw new InsufficientStockException(
                "Insufficient stock for {$this->name} (SKU {$this->sku}): "
                . "requested {$quantity}, available {$this->getAvailableStock()}."
            );
        }

        $this->reservedStock += $quantity;
    }

    public function release(int $quantity): void
    {
        $this->reservedStock = max(0, $this->reservedStock - $quantity);
    }

    public function ship(int $quantity): void
    {
        if ($quantity > $this->reservedStock) {
            throw new InvalidOrderException(
                "Cannot ship {$quantity} of {$this->name} (SKU {$this->sku}): "
                . "only {$this->reservedStock} currently reserved."
            );
        }

        $this->reservedStock -= $quantity;
        $this->soldStock += $quantity;
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'product_type' => $this->getProductType(),
            'total_stock' => $this->totalStock,
            'reserved_stock' => $this->reservedStock,
            'sold_stock' => $this->soldStock,
        ];
    }
}
