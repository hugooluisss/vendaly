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

    public function findItems(int $orderId): array
    {
        return $this->orm->getRepository(OrderItem::class)->select()
            ->where(['orderId' => $orderId])->fetchAll();
    }
}
