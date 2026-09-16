<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\Category;
use App\Domain\Repository\CategoryManagementRepositoryInterface;

final class CycleCategoryRepository extends CycleRepository implements CategoryManagementRepositoryInterface
{
    public function create(Category $entity): Category
    {
        return $this->save($entity);
    }
    public function findById(int $id): ?Category
    {
        return $this->orm->getRepository(Category::class)->findOne(['id' => $id, 'deletedAt' => null]);
    }
    public function findByBusinessId(int $businessId): array
    {
        return $this->orm->getRepository(Category::class)->select()->where(['businessId' => $businessId, 'deletedAt' => null])->orderBy('position')->fetchAll();
    }
    public function update(Category $category): Category
    {
        return $this->save($category);
    }
    public function delete(Category $category): void
    {
        $this->deleteEntity($category);
    }
}
