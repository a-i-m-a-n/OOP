<?php

declare(strict_types=1);

namespace WarehouseSystem\Services;

use WarehouseSystem\Customer;
use WarehouseSystem\InventoryManager;
use WarehouseSystem\Logger;
use WarehouseSystem\Order;
use WarehouseSystem\OrderItem;
use WarehouseSystem\OrderValidator;
use WarehouseSystem\Repository\BundleRepository;
use WarehouseSystem\Repository\OrderRepository;
use WarehouseSystem\Repository\ProductRepository;

class OrderService
{
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private BundleRepository $bundleRepository;
    private InventoryManager $inventoryManager;
    private OrderValidator $orderValidator;
    private Logger $logger;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        BundleRepository $bundleRepository,
        InventoryManager $inventoryManager,
        OrderValidator $orderValidator,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->bundleRepository = $bundleRepository;
        $this->inventoryManager = $inventoryManager;
        $this->orderValidator = $orderValidator;
        $this->logger = $logger;
    }

    /**
     * @param array<int, array{type: string, sku: string, quantity: int}> $itemSpecs
     */
    public function createOrder(Customer $customer, array $itemSpecs): Order
    {
        $items = [];
        foreach ($itemSpecs as $spec) {
            $this->orderValidator->validateItemSpec($spec['type'], $spec['sku'], $spec['quantity']);
            $name = $spec['type'] === OrderItem::TYPE_PRODUCT
                ? $this->productRepository->findBySku($spec['sku'])->getName()
                : $this->bundleRepository->findBySku($spec['sku'])->getName();

            $items[] = new OrderItem($spec['type'], $spec['sku'], $name, $spec['quantity']);
        }

        $this->orderValidator->validateOrderNotEmpty($items);

        $order = $this->orderRepository->createAndSave(
            fn (int $orderId) => new Order($orderId, $customer->getName(), $items)
        );

        $this->logger->info('Order Created', [
            'OrderId' => $order->getOrderId(),
            'Customer' => $customer->getName(),
        ]);

        // Reserve inventory, then persist the order's updated status.
        $this->inventoryManager->reserveOrder($order);
        $this->orderRepository->update($order);

        return $order;
    }

    public function shipOrder(int $orderId): Order
    {
        $order = $this->requireOrder($orderId);
        $this->inventoryManager->shipOrder($order);
        $this->orderRepository->update($order);

        return $order;
    }

    public function cancelOrder(int $orderId): Order
    {
        $order = $this->requireOrder($orderId);
        $this->inventoryManager->cancelOrder($order);
        $this->orderRepository->update($order);

        return $order;
    }

    /**
     * @return Order[]
     */
    public function allOrders(): array
    {
        return $this->orderRepository->all();
    }

    private function requireOrder(int $orderId): Order
    {
        $order = $this->orderRepository->findById($orderId);
        if ($order === null) {
            throw new \RuntimeException("Order #{$orderId} not found.");
        }

        return $order;
    }
}
