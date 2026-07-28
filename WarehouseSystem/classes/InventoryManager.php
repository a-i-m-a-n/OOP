<?php

declare(strict_types=1);

namespace WarehouseSystem;

use WarehouseSystem\Exceptions\BundleException;
use WarehouseSystem\Repository\BundleRepository;
use WarehouseSystem\Repository\ProductRepository;

class InventoryManager
{
    private ProductRepository $productRepository;
    private BundleRepository $bundleRepository;
    private Logger $logger;

    public function __construct(
        ProductRepository $productRepository,
        BundleRepository $bundleRepository,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->bundleRepository = $bundleRepository;
        $this->logger = $logger;
    }

    public function reserveOrder(Order $order): void
    {
        $order->assertCanTransitionTo(Order::STATUS_RESERVED);

        $required = $this->resolveRequiredQuantities($order);
        $this->productRepository->reserveMany($required);
        $order->transitionTo(Order::STATUS_RESERVED);

        foreach ($required as $sku => $qty) {
            $this->logger->info('Inventory Reserved', ['Sku' => $sku, 'Quantity' => $qty]);
        }
        foreach ($order->getItems() as $item) {
            if ($item->getItemType() === OrderItem::TYPE_BUNDLE) {
                $this->logger->info('Bundle Reserved', [
                    'OrderId' => $order->getOrderId(),
                    'Bundle' => $item->getName(),
                    'Quantity' => $item->getQuantity(),
                ]);
            }
        }
    }

    public function shipOrder(Order $order): void
    {
        $order->assertCanTransitionTo(Order::STATUS_SHIPPED);

        $required = $this->resolveRequiredQuantities($order);
        $this->productRepository->shipMany($required);
        $order->transitionTo(Order::STATUS_SHIPPED);

        $this->logger->info('Order Shipped', ['OrderId' => $order->getOrderId()]);
    }

    public function cancelOrder(Order $order): void
    {
        $order->assertCanTransitionTo(Order::STATUS_CANCELLED);

        $required = $this->resolveRequiredQuantities($order);
        $this->productRepository->releaseMany($required);
        $order->transitionTo(Order::STATUS_CANCELLED);

        $this->logger->info('Order Cancelled', ['OrderId' => $order->getOrderId()]);
    }

    /**
     * @return array<string,int> sku => total quantity required
     */
    private function resolveRequiredQuantities(Order $order): array
    {
        $required = [];

        foreach ($order->getItems() as $item) {
            if ($item->getItemType() === OrderItem::TYPE_PRODUCT) {
                $required[$item->getSku()] = ($required[$item->getSku()] ?? 0) + $item->getQuantity();
                continue;
            }

            $bundle = $this->bundleRepository->findBySku($item->getSku());
            if ($bundle === null) {
                throw new BundleException("Unknown bundle SKU: {$item->getSku()}");
            }

            foreach ($bundle->getItems() as $bundleItem) {
                $sku = $bundleItem->getSku();
                $required[$sku] = ($required[$sku] ?? 0) + ($bundleItem->getQuantity() * $item->getQuantity());
            }
        }

        return $required;
    }
}
