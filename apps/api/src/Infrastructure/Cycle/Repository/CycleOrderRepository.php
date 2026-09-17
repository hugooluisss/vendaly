<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\{Order, OrderItem};
use App\Domain\Repository\OrderRepositoryInterface;

final class CycleOrderRepository extends CycleRepository implements OrderRepositoryInterface
{
    public function create(Order $entity): Order
    {
        return $this->save($entity);
    }

    public function createWithItems(Order $entity, array $items): Order
    {
        $driver = $this->orm->getSource(Order::class)->getDatabase()->getDriver();
        $driver->beginTransaction();
        try {
            $manager = new \Cycle\ORM\EntityManager($this->orm);
            $manager->persist($entity)->run(true, \Cycle\ORM\Transaction\Runner::outerTransaction());
            foreach ($items as $item) {
                $item->orderId = (int) $entity->id;
                $manager->persist($item);
            }
            $manager->run(true, \Cycle\ORM\Transaction\Runner::outerTransaction());
            $driver->commitTransaction();
        } catch (\Throwable $e) {
            $driver->rollbackTransaction();
            throw $e;
        }

        return $entity;
    }

    public function createItem(OrderItem $entity): OrderItem
    {
        return $this->save($entity);
    }

    public function findById(int $id): ?Order
    {
        return $this->find(Order::class, $id);
    }

    public function findByBusinessId(int $businessId, ?string $from = null, ?string $to = null): array
    {
        $query = $this->orm->getRepository(Order::class)->select()->where(['businessId' => $businessId]);
        if ($from !== null && $to !== null) {
            $query->where('createdAt', 'BETWEEN', $this->boundary($from, false), $this->boundary($to, true));
        } elseif ($from !== null) {
            $query->where('createdAt', '>=', $this->boundary($from, false));
        } elseif ($to !== null) {
            $query->where('createdAt', '<=', $this->boundary($to, true));
        }
        $orders = $query->orderBy('createdAt', 'DESC')->fetchAll();
        if ($orders === []) return [];
        $items = $this->orm->getRepository(OrderItem::class)->select()
            ->where(['orderId' => ['IN' => array_map(static fn(Order $order): int => (int) $order->id, $orders)]])
            ->fetchAll();
        $itemsByOrder = [];
        foreach ($items as $item) $itemsByOrder[$item->orderId][] = $item;
        return array_map(static fn(Order $order): array => ['order' => $order, 'items' => $itemsByOrder[$order->id] ?? []], $orders);
    }

    public function findItems(int $orderId): array
    {
        return $this->orm->getRepository(OrderItem::class)->select()
            ->where(['orderId' => $orderId])->fetchAll();
    }

    private function boundary(string $date, bool $end): string
    {
        return str_contains($date, 'T') || str_contains($date, ' ') ? $date : $date . ($end ? ' 23:59:59.999999' : ' 00:00:00');
    }
}
