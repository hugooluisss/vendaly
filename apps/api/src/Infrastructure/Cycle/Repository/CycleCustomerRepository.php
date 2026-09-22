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
        $id = (int) $database->query(
            'INSERT INTO customers (business_id, phone) VALUES (?, ?) ON CONFLICT (business_id, phone) DO UPDATE SET id = customers.id RETURNING id',
            [$businessId, $phone],
        )->fetchColumn();
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
