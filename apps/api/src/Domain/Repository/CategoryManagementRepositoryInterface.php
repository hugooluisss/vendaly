<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Category;

interface CategoryManagementRepositoryInterface extends CategoryRepositoryInterface
{
    public function update(Category $category): Category;
    public function delete(Category $category): void;
}
