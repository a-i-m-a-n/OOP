<?php

declare(strict_types=1);

namespace WarehouseSystem\Repository;

use RuntimeException;
use WarehouseSystem\Order;

class OrderRepository extends JsonFileRepository
{
    /**
     * @return Order[]
     */
    public function all(): array
    {
        return array_map(fn (array $row) => Order::fromArray($row), $this->readRows());
    }

    public function findById(int $orderId): ?Order
    {
        foreach ($this->all() as $order) {
            if ($order->getOrderId() === $orderId) {
                return $order;
            }
        }

        return null;
    }

    /**
     * @return Order[]
     */
    public function findByCustomer(string $customerName): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Order $order) => strcasecmp($order->getCustomerName(), $customerName) === 0
        ));
    }

    public function createAndSave(callable $orderFactory): Order
    {
        $created = null;

        $this->transact(function (array $rows) use ($orderFactory, &$created) {
            $maxId = 0;
            foreach ($rows as $row) {
                $maxId = max($maxId, (int) $row['order_id']);
            }

            $order = $orderFactory($maxId + 1);
            $created = $order;
            $rows[] = $order->toArray();

            return $rows;
        });

        return $created;
    }

    public function update(Order $order): void
    {
        $this->transact(function (array $rows) use ($order) {
            $found = false;
            foreach ($rows as $index => $row) {
                if ((int) $row['order_id'] === $order->getOrderId()) {
                    $rows[$index] = $order->toArray();
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("Order #{$order->getOrderId()} not found.");
            }
            return $rows;
        });
    }
}
