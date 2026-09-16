<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\{BusinessRepositoryInterface, CategoryRepositoryInterface, ProductIngredientRepositoryInterface, ProductRepositoryInterface};

final readonly class CatalogService
{
    public function __construct(
        private BusinessRepositoryInterface $businesses,
        private CategoryRepositoryInterface $categories,
        private ProductRepositoryInterface $products,
        private ?ProductIngredientRepositoryInterface $ingredients = null,
    ) {
    }
    public function publicCatalog(string $slug): ?array
    {
        $business = $this->businesses->findPublishedBySlug($slug);
        if ($business === null) {
            return null;
        }
        $categories = $this->categories->findByBusinessId((int) $business->id);
        $products = $this->products->findActiveByBusinessId((int) $business->id);
        $ingredientNames = $this->ingredients?->findByProductIds(array_map(static fn($product) => (int) $product->id, $products)) ?? [];
        $byCategory = [];
        foreach ($products as $product) {
            $byCategory[$product->categoryId][] = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'position' => $product->position,
                'ingredients' => $ingredientNames[$product->id] ?? [],
            ];
        }
        return [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'logo_url' => $business->logoUrl,
                'description' => $business->description,
            ],
            'hours' => array_map(static fn($h) => [
                'day_of_week' => $h->dayOfWeek,
                'opens_at' => $h->opensAt,
                'closes_at' => $h->closesAt,
                'is_closed' => $h->isClosed,
            ], $this->businesses->findHours((int) $business->id)),
            'categories' => array_map(static fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'position' => $c->position,
                'products' => $byCategory[$c->id] ?? [],
            ], $categories),
        ];
    }
}
