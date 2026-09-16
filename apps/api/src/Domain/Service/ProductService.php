<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Product, ProductImage};
use App\Domain\Repository\{CategoryRepositoryInterface, ObjectStorageInterface, ProductIngredientRepositoryInterface, ProductManagementRepositoryInterface};
use DomainException;

final readonly class ProductService
{
    public function __construct(
        private ProductManagementRepositoryInterface $products,
        private CategoryRepositoryInterface $categories,
        private BusinessMemberGuard $guard,
        private ObjectStorageInterface $storage,
        private ?ProductIngredientRepositoryInterface $ingredients = null,
    ) {
    }
    public function list(int $userId, int $businessId): array
    {
        $this->guard->assertOwner($userId, $businessId);
        return $this->products->findByBusinessId($businessId);
    }
    public function create(int $userId, int $businessId, array $input, ?string $image = null, string $contentType = 'application/octet-stream'): Product
    {
        $this->guard->assertOwner($userId, $businessId);
        $categoryId = (int) ($input['category_id'] ?? 0);
        $category = $this->categories->findById($categoryId);
        if ($category === null || $category->businessId !== $businessId) throw new DomainException('Category not found.');
        $product = new Product();
        $product->businessId = $businessId;
        $product->categoryId = $categoryId;
        $product->name = trim((string) ($input['name'] ?? ''));
        $product->description = array_key_exists('description', $input) ? ($input['description'] === null ? null : (string) $input['description']) : null;
        $product->price = array_key_exists('price', $input) && $input['price'] !== '' && $input['price'] !== null ? (string) $input['price'] : null;
        $product->isActive = (bool) ($input['is_active'] ?? true);
        $product->position = (int) ($input['position'] ?? 0);
        $product->createdAt = date(DATE_ATOM);
        if ($product->name === '') throw new DomainException('Product name is required.');
        $product = $this->products->create($product);
        $this->replaceIngredients($product, $input);
        if ($image !== null) $this->saveImage($product, $image, $contentType);
        return $product;
    }
    public function update(int $userId, int $businessId, int $productId, array $input, ?string $image = null, string $contentType = 'application/octet-stream'): Product
    {
        $product = $this->owned($userId, $businessId, $productId);
        if (array_key_exists('category_id', $input)) {
            $category = $this->categories->findById((int) $input['category_id']);
            if ($category === null || $category->businessId !== $businessId) throw new DomainException('Category not found.');
            $product->categoryId = (int) $input['category_id'];
        }
        foreach (['name', 'description'] as $field) if (array_key_exists($field, $input)) $product->$field = $input[$field];
        if (array_key_exists('price', $input)) $product->price = $input['price'] === '' || $input['price'] === null ? null : (string) $input['price'];
        if (array_key_exists('is_active', $input)) $product->isActive = (bool) $input['is_active'];
        if (array_key_exists('position', $input)) $product->position = (int) $input['position'];
        $product = $this->products->update($product);
        $this->replaceIngredients($product, $input);
        if ($image !== null) $this->saveImage($product, $image, $contentType);
        return $product;
    }
    public function delete(int $userId, int $businessId, int $productId): void
    {
        $product = $this->owned($userId, $businessId, $productId);
        $product->deletedAt = date(DATE_ATOM);
        $this->products->update($product);
    }
    public function setActive(int $userId, int $businessId, int $productId, bool $active): Product
    {
        $product = $this->owned($userId, $businessId, $productId);
        $product->isActive = $active;
        return $this->products->update($product);
    }
    private function saveImage(Product $product, string $contents, string $contentType): void
    {
        $url = $this->storage->put('products/' . $product->id . '/image', $contents, $contentType);
        $image = $this->products->findImage((int) $product->id) ?? new ProductImage();
        $image->productId = (int) $product->id;
        $image->url = $url;
        $image->createdAt = date(DATE_ATOM);
        $this->products->saveImage($image);
    }
    private function replaceIngredients(Product $product, array $input): void
    {
        if (!array_key_exists('ingredients', $input)) return;
        if (!is_array($input['ingredients']) || array_filter($input['ingredients'], static fn($name) => !is_string($name)) !== []) throw new DomainException('Ingredients must be an array of strings.');
        $this->ingredients?->replaceForProduct((int) $product->id, $input['ingredients']);
    }
    private function owned(int $userId, int $businessId, int $productId): Product
    {
        $this->guard->assertOwner($userId, $businessId);
        $product = $this->products->findById($productId);
        if ($product === null || $product->businessId !== $businessId) throw new DomainException('Product not found.');
        return $product;
    }
}
