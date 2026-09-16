<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface ProductIngredientRepositoryInterface
{
    /** @param string[] $names */
    public function replaceForProduct(int $productId, array $names): void;
    /** @param int[] $productIds @return array<int, string[]> */
    public function findByProductIds(array $productIds): array;
}
