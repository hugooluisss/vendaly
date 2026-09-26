<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\Customer;
use App\Domain\Repository\CustomerRepositoryInterface;

final class CycleCustomerRepository extends CycleRepository implements CustomerRepositoryInterface
{
    public function findOrCreateByPhone(int $businessId, string $phone): Customer
    {
        $database = $this->orm->getSource(Customer::class)->getDatabase();
        $database->execute(
            'INSERT INTO customers (business_id, phone) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
            [$businessId, $phone],
        );
        $id = (int) $database->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        return $this->find(Customer::class, $id);
    }

    public function findByIdsForBusiness(int $businessId, array $ids): array
    {
        if ($ids === []) return [];
        $customers = $this->orm->getRepository(Customer::class)->select()
            ->where(['businessId' => $businessId, 'id' => ['IN' => array_values(array_unique($ids))]])->fetchAll();
        $byId = [];
        foreach ($customers as $customer) $byId[(int) $customer->id] = $customer;
        return $byId;
    }
}
