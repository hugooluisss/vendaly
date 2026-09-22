<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\{Order, OrderItem, OrderStatus};
use App\Domain\Repository\OrderRepositoryInterface;
use App\Infrastructure\Cycle\Repository\CycleCustomerRepository;

final class CycleOrderRepository extends CycleRepository implements OrderRepositoryInterface
{
    public function create(Order $entity): Order
    {
        return $this->createWithItems($entity, []);
    }

    public function createWithItems(Order $entity, array $items, array $options = [], ?string $phone = null): Order
    {
        $driver = $this->orm->getSource(Order::class)->getDatabase()->getDriver();
        $database = $this->orm->getSource(Order::class)->getDatabase();
        $driver->beginTransaction();
        try {
            if ($phone !== null) {
                $entity->customerId = (new CycleCustomerRepository($this->orm))->findOrCreateByPhone($entity->businessId, $phone)->id;
            }
            if ($entity->orderNumber === null) {
                $row = $database->query(
                    'UPDATE businesses SET next_order_number = next_order_number + 1 WHERE id = ? RETURNING next_order_number - 1 AS order_number',
                    [$entity->businessId],
                )->fetch();
                if ($row === false) {
                    throw new \DomainException('Business not found.');
                }
                $entity->orderNumber = (int) $row['order_number'];
            }
            if ($entity->statusId === null) {
                $defaultStatus = $database->query(
                    'SELECT id FROM order_statuses WHERE business_id = ? AND is_default = TRUE LIMIT 1',
                    [$entity->businessId],
                )->fetchColumn();
                if ($defaultStatus === false) {
                    foreach (OrderStatus::defaults() as $defaultData) {
                        $terminal = $defaultData['is_terminal'] ? 'TRUE' : 'FALSE';
                        $default = $defaultData['is_default'] ? 'TRUE' : 'FALSE';
                        $database->execute(
                            "INSERT INTO order_statuses (business_id, name, color, is_terminal, is_default, position) VALUES (?, ?, ?, {$terminal}, {$default}, ?)",
                            [$entity->businessId, $defaultData['name'], $defaultData['color'], $defaultData['position']],
                        );
                    }
                    $defaultStatus = $database->query('SELECT id FROM order_statuses WHERE business_id = ? AND is_default = TRUE LIMIT 1', [$entity->businessId])->fetchColumn();
                }
                $entity->statusId = (int) $defaultStatus;
            }
            $manager = new \Cycle\ORM\EntityManager($this->orm);
            $manager->persist($entity)->run(true, \Cycle\ORM\Transaction\Runner::outerTransaction());
            foreach ($items as $index => $item) {
                $item->orderId = (int) $entity->id;
                $manager->persist($item);
                $manager->run(true, \Cycle\ORM\Transaction\Runner::outerTransaction());
                foreach ($options[$index] ?? [] as $option) {
                    $option->orderItemId = (int) $item->id;
                    $manager->persist($option);
                }
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
        if ($orders === []) {
            return [];
        }
        $items = $this->orm->getRepository(OrderItem::class)->select()
            ->where(['orderId' => ['IN' => array_map(static fn(Order $order): int => (int) $order->id, $orders)]])
            ->fetchAll();
        $itemsByOrder = [];
        foreach ($items as $item) {
            $itemsByOrder[$item->orderId][] = $item;
        }
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
