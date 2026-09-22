<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Customer;

interface CustomerRepositoryInterface
{
    public function findOrCreateByPhone(int $businessId, string $phone): Customer;

    /** @param int[] $ids @return array<int, Customer> */
    public function findByIdsForBusiness(int $businessId, array $ids): array;
}
