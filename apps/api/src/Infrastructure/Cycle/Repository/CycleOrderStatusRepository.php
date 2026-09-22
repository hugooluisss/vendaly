<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\OrderStatus;
use App\Domain\Repository\OrderStatusRepositoryInterface;

final class CycleOrderStatusRepository extends CycleRepository implements OrderStatusRepositoryInterface
{
    public function findByBusinessId(int $businessId): array
    {
        return $this->orm->getRepository(OrderStatus::class)->select()->where(['businessId' => $businessId])->orderBy('position')->fetchAll();
    }

    public function findById(int $id): ?OrderStatus
    {
        return $this->find(OrderStatus::class, $id);
    }

    public function create(OrderStatus $status): OrderStatus { return $this->save($status); }
    public function update(OrderStatus $status): OrderStatus { return $this->save($status); }
    public function delete(OrderStatus $status): void { $this->deleteEntity($status); }

    public function existsOrderWithStatus(int $statusId): bool
    {
        return $this->orm->getSource(OrderStatus::class)->getDatabase()->query(
            'SELECT 1 FROM orders WHERE status_id = ? LIMIT 1',
            [$statusId],
        )->fetch() !== false;
    }
}
