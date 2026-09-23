<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Repository\{BusinessRepositoryInterface, CatalogScanRepositoryInterface, CategoryRepositoryInterface, PaymentMethodRepositoryInterface, ProductIngredientRepositoryInterface, ProductOptionRepositoryInterface, ProductRepositoryInterface};
use DateTimeImmutable;

final readonly class CatalogService
{
    public function __construct(
        private BusinessRepositoryInterface $businesses,
        private CategoryRepositoryInterface $categories,
        private ProductRepositoryInterface $products,
        private ?ProductIngredientRepositoryInterface $ingredients = null,
        private ?ProductOptionRepositoryInterface $options = null,
        private ?PaymentMethodRepositoryInterface $paymentMethods = null,
        private ?CatalogScanRepositoryInterface $scans = null,
    ) {}
    public function publicCatalog(string $slug): ?array
    {
        $business = $this->businesses->findPublishedBySlug($slug);
        if ($business === null) {
            return null;
        }
        $categories = $this->categories->findByBusinessId((int) $business->id);
        $products = $this->products->findActiveByBusinessId((int) $business->id);
        $ingredientNames = $this->ingredients?->findByProductIds(array_map(static fn($product) => (int) $product->id, $products)) ?? [];
        $optionGroups = $this->options?->findByProductIds(array_map(static fn($product) => (int) $product->id, $products)) ?? [];
        $byCategory = [];
        foreach ($products as $product) {
            $byCategory[$product->categoryId][] = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'position' => $product->position,
                'ingredients' => $ingredientNames[$product->id] ?? [],
                'options' => array_map(static fn($option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'selection_type' => $option->selectionType,
                    'required' => $option->required,
                    'position' => $option->position,
                    'values' => array_map(static fn($value): array => [
                        'id' => $value->id,
                        'name' => $value->name,
                        'price_delta' => $value->priceDelta,
                        'position' => $value->position,
                    ], $option->values),
                ], $optionGroups[$product->id] ?? []),
            ];
        }
        return [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'logo_url' => $business->logoUrl,
                'cover_image_url' => $business->coverImageUrl,
                'description' => $business->description,
            ],
            'fulfillment_methods' => array_values(array_filter([
                $business->pickupEnabled ? ['type' => 'pickup', 'fee' => $business->pickupFee] : null,
                $business->deliveryEnabled ? ['type' => 'delivery', 'fee' => $business->deliveryFee] : null,
                $business->dineInEnabled ? ['type' => 'dine_in', 'fee' => $business->dineInFee] : null,
            ])),
            'payment_methods' => array_map(static fn($method): array => ['id' => $method->id, 'name' => $method->name], $this->paymentMethods?->findByBusinessId((int) $business->id) ?? []),
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

    public function recordScan(string $slug): bool
    {
        $business = $this->businesses->findPublishedBySlug($slug);
        if ($business === null) {
            return false;
        }
        if ($this->scans === null) {
            throw new \LogicException('Catalog scan repository is not configured.');
        }
        $this->scans->record((int) $business->id);
        return true;
    }

    public function scanStats(int $businessId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if ($this->scans === null) {
            throw new \LogicException('Catalog scan repository is not configured.');
        }
        return [
            'total' => $this->scans->countTotal($businessId),
            'by_day' => $this->scans->countByDay($businessId, $from, $to),
        ];
    }
}
