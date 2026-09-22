<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\OrderStatus;

interface OrderStatusRepositoryInterface
{
    /** @return OrderStatus[] */
    public function findByBusinessId(int $businessId): array;
    public function findById(int $id): ?OrderStatus;
    public function create(OrderStatus $status): OrderStatus;
    public function update(OrderStatus $status): OrderStatus;
    public function delete(OrderStatus $status): void;
    public function existsOrderWithStatus(int $statusId): bool;
}
