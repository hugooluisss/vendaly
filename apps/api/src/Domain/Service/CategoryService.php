<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Category;
use App\Domain\Repository\{CategoryManagementRepositoryInterface, ProductManagementRepositoryInterface};
use DomainException;

final readonly class CategoryService
{
    public function __construct(private CategoryManagementRepositoryInterface $categories, private ProductManagementRepositoryInterface $products, private BusinessMemberGuard $guard)
    {
    }
    public function list(int $userId, int $businessId): array
    {
        $this->guard->assertOwner($userId, $businessId);
        return $this->categories->findByBusinessId($businessId);
    }
    public function create(int $userId, int $businessId, string $name, int $position = 0): Category
    {
        $this->guard->assertOwner($userId, $businessId);
        $category = new Category();
        $category->businessId = $businessId;
        $category->name = trim($name);
        $category->position = $position;
        if ($category->name === '') throw new DomainException('Category name is required.');
        return $this->categories->create($category);
    }
    public function update(int $userId, int $businessId, int $categoryId, array $input): Category
    {
        $category = $this->owned($userId, $businessId, $categoryId);
        if (array_key_exists('name', $input)) $category->name = trim((string) $input['name']);
        if (array_key_exists('position', $input)) $category->position = (int) $input['position'];
        return $this->categories->update($category);
    }
    public function reorder(int $userId, int $businessId, array $positions): array
    {
        $this->guard->assertOwner($userId, $businessId);
        foreach ($positions as $id => $position) {
            $category = $this->owned($userId, $businessId, (int) $id);
            $category->position = (int) $position;
            $this->categories->update($category);
        }
        return $this->categories->findByBusinessId($businessId);
    }
    public function delete(int $userId, int $businessId, int $categoryId): void
    {
        $category = $this->owned($userId, $businessId, $categoryId);
        if ($this->products->findByCategoryId($categoryId) !== []) {
            throw new DomainException('Cannot delete a category that contains products.');
        }
        $category->deletedAt = date(DATE_ATOM);
        $this->categories->update($category);
    }
    private function owned(int $userId, int $businessId, int $categoryId): Category
    {
        $this->guard->assertOwner($userId, $businessId);
        $category = $this->categories->findById($categoryId);
        if ($category === null || $category->businessId !== $businessId) throw new DomainException('Category not found.');
        return $category;
    }
}
