<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Category;

interface CategoryRepositoryInterface
{
    public function create(Category $entity): Category;
    public function findById(int $id): ?Category;
    /** @return Category[] */ public function findByBusinessId(int $businessId): array;
}
