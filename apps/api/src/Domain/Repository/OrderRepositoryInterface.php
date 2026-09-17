<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\{Order, OrderItem};

interface OrderRepositoryInterface
{
    public function create(Order $entity): Order;
    /** @param OrderItem[] $items */
    public function createWithItems(Order $entity, array $items): Order;
    public function createItem(OrderItem $entity): OrderItem;
    public function findById(int $id): ?Order;
    /** @return list<array{order: Order, items: OrderItem[]}> */
    public function findByBusinessId(int $businessId, ?string $from = null, ?string $to = null): array;
    /** @return OrderItem[] */
    public function findItems(int $orderId): array;
}
