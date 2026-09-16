<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Product;

interface ProductRepositoryInterface
{
    public function create(Product $entity): Product;
    public function findById(int $id): ?Product;
    /** @return Product[] */
    public function findActiveByBusinessId(int $businessId): array;
    /** @return Product[] */
    public function findActiveByIdsForBusiness(array $ids, int $businessId): array;
}
