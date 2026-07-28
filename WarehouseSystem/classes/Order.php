<?php

declare(strict_types=1);

namespace WarehouseSystem;

use WarehouseSystem\Exceptions\InvalidOrderException;

class Order
{
    public const STATUS_CREATED = 'Created';
    public const STATUS_RESERVED = 'Reserved';
    public const STATUS_SHIPPED = 'Shipped';
    public const STATUS_CANCELLED = 'Cancelled';

    private const TRANSITIONS = [
        self::STATUS_CREATED => [self::STATUS_RESERVED, self::STATUS_CANCELLED],
        self::STATUS_RESERVED => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [],
        self::STATUS_CANCELLED => [],
    ];

    private int $orderId;
    private string $customerName;
    /** @var OrderItem[] */
    private array $items;
    private string $status;
    private string $createdAt;

    /**
     * @param OrderItem[] $items
     */
    public function __construct(
        int $orderId,
        string $customerName,
        array $items,
        string $status = self::STATUS_CREATED,
        ?string $createdAt = null
    ) {
        $this->orderId = $orderId;
        $this->customerName = $customerName;
        $this->items = $items;
        $this->status = $status;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    /**
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function assertCanTransitionTo(string $newStatus): void
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidOrderException(
                "Order #{$this->orderId} cannot move from '{$this->status}' to '{$newStatus}'."
            );
        }
    }

    public function transitionTo(string $newStatus): void
    {
        $this->assertCanTransitionTo($newStatus);
        $this->status = $newStatus;
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'customer_name' => $this->customerName,
            'items' => array_map(fn (OrderItem $i) => $i->toArray(), $this->items),
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $items = array_map(
            fn (array $row) => OrderItem::fromArray($row),
            $data['items'] ?? []
        );

        return new self(
            (int) $data['order_id'],
            $data['customer_name'],
            $items,
            $data['status'] ?? self::STATUS_CREATED,
            $data['created_at'] ?? null
        );
    }
}
