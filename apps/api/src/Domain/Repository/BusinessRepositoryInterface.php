<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\{Business, BusinessHours};

interface BusinessRepositoryInterface
{
    public function create(Business $entity): Business;
    public function findById(int $id): ?Business;
    public function findPublishedBySlug(string $slug): ?Business;
    /** @return Business[] */
    public function findPublishedDirectory(?string $category, ?string $location, ?float $latitude = null, ?float $longitude = null, ?string $name = null): array;
    /** @return BusinessHours[] */
    public function findHours(int $businessId): array;
}
