<?php

declare(strict_types=1);

namespace App\Web\Shared;

use App\Domain\Entity\{Business, BusinessHours, Category, OrderStatus, PaymentMethod, Product};

final class PublicEntityMapper
{
    public static function business(Business $business, array $paymentMethods = [], array $orderStatuses = []): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'slug' => $business->slug,
            'logo_url' => $business->logoUrl,
            'cover_image_url' => $business->coverImageUrl,
            'whatsapp_number' => $business->whatsappNumber,
            'description' => $business->description,
            'is_published' => $business->isPublished,
            'latitude' => $business->latitude,
            'longitude' => $business->longitude,
            'pickup_enabled' => $business->pickupEnabled,
            'delivery_enabled' => $business->deliveryEnabled,
            'dine_in_enabled' => $business->dineInEnabled,
            'pickup_fee' => $business->pickupFee,
            'delivery_fee' => $business->deliveryFee,
            'dine_in_fee' => $business->dineInFee,
            'payment_methods' => array_map(self::paymentMethod(...), $paymentMethods),
            'order_statuses' => array_map(self::orderStatus(...), $orderStatuses),
        ];
    }

    public static function paymentMethod(PaymentMethod $method): array
    {
        return ['id' => $method->id, 'name' => $method->name, 'position' => $method->position];
    }

    public static function orderStatus(OrderStatus $status): array
    {
        return ['id' => $status->id, 'name' => $status->name, 'color' => $status->color, 'is_terminal' => $status->isTerminal, 'is_default' => $status->isDefault, 'position' => $status->position];
    }

    public static function hours(BusinessHours $hours): array
    {
        return [
            'day_of_week' => $hours->dayOfWeek,
            'opens_at' => $hours->opensAt,
            'closes_at' => $hours->closesAt,
            'is_closed' => $hours->isClosed,
        ];
    }

    public static function category(Category $category): array
    {
        return ['id' => $category->id, 'name' => $category->name, 'position' => $category->position];
    }

    public static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->categoryId,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'is_active' => $product->isActive,
            'position' => $product->position,
            'ingredients' => $product->ingredients ?? [],
            'options' => array_map(static fn($option): array => [
                'id' => $option->id, 'name' => $option->name, 'selection_type' => $option->selectionType,
                'required' => $option->required, 'position' => $option->position,
                'values' => array_map(static fn($value): array => ['id' => $value->id, 'name' => $value->name, 'price_delta' => $value->priceDelta, 'position' => $value->position], $option->values),
            ], $product->options ?? []),
        ];
    }
}
