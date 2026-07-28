<?php

declare(strict_types=1);

namespace WarehouseSystem;

class BundleItem
{
    private string $sku;
    private int $quantity;

    public function __construct(string $sku, int $quantity)
    {
        $this->sku = $sku;
        $this->quantity = $quantity;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function toArray(): array
    {
        return ['sku' => $this->sku, 'quantity' => $this->quantity];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['sku'], (int) $data['quantity']);
    }
}
