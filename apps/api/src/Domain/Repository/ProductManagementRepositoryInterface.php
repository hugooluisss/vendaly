<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\{Product, ProductImage};

interface ProductManagementRepositoryInterface extends ProductRepositoryInterface
{
    public function update(Product $product): Product;
    public function delete(Product $product): void;
    public function findByBusinessId(int $businessId): array;
    public function findByCategoryId(int $categoryId): array;
    public function findImage(int $productId): ?ProductImage;
    public function saveImage(ProductImage $image): ProductImage;
    public function deleteImage(ProductImage $image): void;
}
