<?php

declare(strict_types=1);

namespace WarehouseSystem\Repository;

use WarehouseSystem\Bundle;
use WarehouseSystem\Exceptions\BundleException;

class BundleRepository extends JsonFileRepository
{
    /**
     * @return Bundle[]
     */
    public function all(): array
    {
        return array_map(fn (array $row) => Bundle::fromArray($row), $this->readRows());
    }

    public function findBySku(string $sku): ?Bundle
    {
        foreach ($this->all() as $bundle) {
            if ($bundle->getSku() === $sku) {
                return $bundle;
            }
        }

        return null;
    }

    public function save(Bundle $bundle): void
    {
        $this->transact(function (array $rows) use ($bundle) {
            foreach ($rows as $row) {
                if ($row['sku'] === $bundle->getSku()) {
                    throw new BundleException("A bundle with SKU '{$bundle->getSku()}' already exists.");
                }
            }
            $rows[] = $bundle->toArray();
            return $rows;
        });
    }
}
