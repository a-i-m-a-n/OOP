<?php

declare(strict_types=1);

namespace WarehouseSystem;

use WarehouseSystem\Exceptions\BundleException;
use WarehouseSystem\Exceptions\InvalidOrderException;
use WarehouseSystem\Repository\BundleRepository;
use WarehouseSystem\Repository\ProductRepository;

class OrderValidator
{
    private ProductRepository $productRepository;
    private BundleRepository $bundleRepository;

    public function __construct(ProductRepository $productRepository, BundleRepository $bundleRepository)
    {
        $this->productRepository = $productRepository;
        $this->bundleRepository = $bundleRepository;
    }

    public function validateItemSpec(string $itemType, string $sku, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidOrderException('Quantity must be greater than 0.');
        }

        if ($itemType === OrderItem::TYPE_PRODUCT) {
            $product = $this->productRepository->findBySku($sku);
            if ($product === null) {
                throw new InvalidOrderException("Unknown product SKU: {$sku}");
            }
            // Fail fast on obviously-doomed requests; the authoritative
            // check still happens inside the locked reservation.
            $product->validateOrderQuantity($quantity);
        } elseif ($itemType === OrderItem::TYPE_BUNDLE) {
            $bundle = $this->bundleRepository->findBySku($sku);
            if ($bundle === null) {
                throw new BundleException("Unknown bundle SKU: {$sku}");
            }
            foreach ($bundle->getItems() as $bundleItem) {
                if (!$this->productRepository->exists($bundleItem->getSku())) {
                    throw new BundleException(
                        "Bundle '{$bundle->getName()}' references unknown product SKU: {$bundleItem->getSku()}"
                    );
                }
            }
        } else {
            throw new InvalidOrderException("Unknown item type: {$itemType}");
        }
    }

    /**
     * @param OrderItem[] $items
     */
    public function validateOrderNotEmpty(array $items): void
    {
        if (empty($items)) {
            throw new InvalidOrderException('An order must contain at least one product or bundle.');
        }
    }
}
