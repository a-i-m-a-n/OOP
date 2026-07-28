<?php

declare(strict_types=1);

namespace WarehouseSystem;

class Bundle implements PurchasableInterface
{
    private string $sku;
    private string $name;
    /** @var BundleItem[] */
    private array $items;

    /**
     * @param BundleItem[] $items
     */
    public function __construct(string $sku, string $name, array $items)
    {
        $this->sku = $sku;
        $this->name = $name;
        $this->items = $items;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProductType(): string
    {
        return 'bundle';
    }

    /**
     * @return BundleItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'items' => array_map(fn (BundleItem $i) => $i->toArray(), $this->items),
        ];
    }

    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn (array $row) => BundleItem::fromArray($row),
            $data['items'] ?? []
        );

        return new self($data['sku'], $data['name'], $items);
    }
}
