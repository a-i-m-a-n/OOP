<?php

declare(strict_types=1);

namespace WarehouseSystem\Repository;

use WarehouseSystem\Exceptions\InsufficientStockException;
use WarehouseSystem\Exceptions\InvalidOrderException;
use WarehouseSystem\Factory\ProductFactory;
use WarehouseSystem\Product;

class ProductRepository extends JsonFileRepository
{
    /**
     * @return Product[]
     */
    public function all(): array
    {
        return array_map(fn (array $row) => ProductFactory::fromArray($row), $this->readRows());
    }

    public function findBySku(string $sku): ?Product
    {
        foreach ($this->all() as $product) {
            if ($product->getSku() === $sku) {
                return $product;
            }
        }

        return null;
    }

    public function exists(string $sku): bool
    {
        return $this->findBySku($sku) !== null;
    }

    public function save(Product $product): void
    {
        $this->transact(function (array $rows) use ($product) {
            foreach ($rows as $row) {
                if ($row['sku'] === $product->getSku()) {
                    throw new InvalidOrderException("A product with SKU '{$product->getSku()}' already exists.");
                }
            }
            $rows[] = $product->toArray();
            return $rows;
        });
    }


    public function reserveMany(array $requiredBySku): void
    {
        $this->mutateMany($requiredBySku, function (Product $product, int $qty) {
            $product->validateOrderQuantity($qty);
            if ($qty > $product->getAvailableStock()) {
                throw new InsufficientStockException(
                    "Insufficient stock for {$product->getName()} (SKU {$product->getSku()}): "
                    . "requested {$qty}, available {$product->getAvailableStock()}."
                );
            }
        }, function (Product $product, int $qty) {
            $product->reserve($qty);
        });
    }

    public function shipMany(array $requiredBySku): void
    {
        $this->mutateMany($requiredBySku, function (Product $product, int $qty) {
            // Product::ship() itself validates reserved >= qty.
        }, function (Product $product, int $qty) {
            $product->ship($qty);
        });
    }
    public function releaseMany(array $requiredBySku): void
    {
        $this->mutateMany($requiredBySku, function (Product $product, int $qty) {
            // release() is always safe (clamped at 0), nothing to pre-validate.
        }, function (Product $product, int $qty) {
            $product->release($qty);
        });
    }

    /**
     *
     * @param array<string,int> $requiredBySku
     */
    private function mutateMany(array $requiredBySku, callable $validate, callable $apply): void
    {
        $this->transact(function (array $rows) use ($requiredBySku, $validate, $apply) {
            $products = [];
            foreach ($rows as $row) {
                $products[$row['sku']] = ProductFactory::fromArray($row);
            }

            foreach ($requiredBySku as $sku => $qty) {
                if (!isset($products[$sku])) {
                    throw new InvalidOrderException("Unknown product SKU: {$sku}");
                }
            }

            foreach ($requiredBySku as $sku => $qty) {
                $validate($products[$sku], $qty);
            }

            foreach ($requiredBySku as $sku => $qty) {
                $apply($products[$sku], $qty);
            }

            return array_map(fn (Product $p) => $p->toArray(), array_values($products));
        });
    }
}
