<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Business, Order, OrderItem, Product};
use App\Domain\Repository\{BusinessRepositoryInterface, OrderRepositoryInterface, ProductRepositoryInterface};
use DomainException;

final readonly class OrderService
{
    public function __construct(
        private BusinessRepositoryInterface $businesses,
        private ProductRepositoryInterface $products,
        private OrderRepositoryInterface $orders,
    ) {}

    /** @param array{items?: list<array{product_id:int,quantity:int,note?:string}>,customer_note?:string} $input */
    public function create(string $slug, array $input): array
    {
        $business = $this->businesses->findPublishedBySlug($slug);
        $this->validateBusiness($business);
        $inputItems = $input['items'] ?? [];
        if ($inputItems === []) {
            throw new DomainException('Order must contain at least one item.');
        }

        $products = $this->loadProducts($inputItems, (int) $business->id);
        [$order, $orderItems] = $this->buildOrder($business, $input, $inputItems, $products);
        $this->orders->createWithItems($order, $orderItems);

        return ['order' => $order, 'items' => $orderItems, 'whatsapp_number' => $business->whatsappNumber];
    }

    private function validateBusiness(?Business $business): void
    {
        if ($business === null) {
            throw new DomainException('Catalog not found.');
        }
        if ($business->whatsappNumber === null || trim($business->whatsappNumber) === '') {
            throw new DomainException('Business cannot currently receive orders.');
        }
    }

    private function loadProducts(array $inputItems, int $businessId): array
    {
        $ids = array_map(static fn(array $item): int => (int) ($item['product_id'] ?? 0), $inputItems);
        $products = [];
        foreach ($this->products->findActiveByIdsForBusiness(array_values(array_unique($ids)), $businessId) as $product) {
            $products[$product->id] = $product;
        }
        return $products;
    }

    private function buildOrder(Business $business, array $input, array $inputItems, array $products): array
    {
        $order = new Order();
        $order->businessId = (int) $business->id;
        $order->customerNote = $input['customer_note'] ?? null;
        $order->createdAt = date(DATE_ATOM);
        $orderItems = [];
        $totalCents = 0;
        $hasPrice = false;
        foreach ($inputItems as $inputItem) {
            $quantity = (int) ($inputItem['quantity'] ?? 0);
            if ($quantity < 1) {
                throw new DomainException('Quantity must be positive.');
            }
            $product = $products[(int) ($inputItem['product_id'] ?? 0)] ?? null;
            if (!$product instanceof Product) {
                throw new DomainException('Product is not available.');
            }
            $orderItems[] = $this->buildItem($product, $quantity, $inputItem['note'] ?? null);
            if ($product->price !== null) {
                $hasPrice = true;
                $totalCents += (int) round((float) $product->price * 100) * $quantity;
            }
        }
        $order->total = $hasPrice ? number_format($totalCents / 100, 2, '.', '') : null;
        return [$order, $orderItems];
    }

    private function buildItem(Product $product, int $quantity, ?string $note): OrderItem
    {
        $item = new OrderItem();
        $item->productId = (int) $product->id;
        $item->productNameSnapshot = $product->name;
        $item->unitPriceSnapshot = $product->price;
        $item->quantity = $quantity;
        $item->note = $note;
        return $item;
    }
}
