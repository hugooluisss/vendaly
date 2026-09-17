<?php

declare(strict_types=1);

namespace App\Web\Shared;

use App\Domain\Entity\{Business, BusinessHours, Category, Product};

final class PublicEntityMapper
{
    public static function business(Business $business): array
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
        ];
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
        ];
    }
}
