<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\Product;
use App\Domain\Entity\ProductImage;
use App\Domain\Repository\ProductManagementRepositoryInterface;

final class CycleProductRepository extends CycleRepository implements ProductManagementRepositoryInterface
{
    public function create(Product $entity): Product
    {
        return $this->save($entity);
    }

    public function findById(int $id): ?Product
    {
        return $this->orm->getRepository(Product::class)->findOne(['id' => $id, 'deletedAt' => null]);
    }

    public function findActiveByBusinessId(int $businessId): array
    {
        return $this->orm->getRepository(Product::class)->select()
            ->where(['businessId' => $businessId, 'isActive' => true, 'deletedAt' => null])
            ->orderBy('position')->fetchAll();
    }

    public function findActiveByIdsForBusiness(array $ids, int $businessId): array
    {
        return $this->orm->getRepository(Product::class)->select()
            ->where(['id' => ['IN' => $ids], 'businessId' => $businessId, 'isActive' => true, 'deletedAt' => null])
            ->fetchAll();
    }
    public function update(Product $product): Product
    {
        return $this->save($product);
    }
    public function delete(Product $product): void
    {
        $this->deleteEntity($product);
    }
    public function findByBusinessId(int $businessId): array
    {
        return $this->orm->getRepository(Product::class)->select()->where(['businessId' => $businessId, 'deletedAt' => null])->orderBy('position')->fetchAll();
    }
    public function findByCategoryId(int $categoryId): array
    {
        return $this->orm->getRepository(Product::class)->select()->where(['categoryId' => $categoryId, 'deletedAt' => null])->fetchAll();
    }
    public function findImage(int $productId): ?ProductImage
    {
        return $this->orm->getRepository(ProductImage::class)->findOne(['productId' => $productId]);
    }
    public function saveImage(ProductImage $image): ProductImage
    {
        return $this->save($image);
    }
    public function deleteImage(ProductImage $image): void
    {
        $this->deleteEntity($image);
    }
}
