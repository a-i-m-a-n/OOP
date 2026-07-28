<?php

declare(strict_types=1);

namespace WarehouseSystem;

class OrderItem
{
    public const TYPE_PRODUCT = 'product';
    public const TYPE_BUNDLE = 'bundle';

    private string $itemType;
    private string $sku;
    private string $name;
    private int $quantity;

    public function __construct(string $itemType, string $sku, string $name, int $quantity)
    {
        $this->itemType = $itemType;
        $this->sku = $sku;
        $this->name = $name;
        $this->quantity = $quantity;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'item_type' => $this->itemType,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['item_type'], $data['sku'], $data['name'], (int) $data['quantity']);
    }
}
