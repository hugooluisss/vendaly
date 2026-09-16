<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\{Business, BusinessHours};

interface BusinessRepositoryInterface
{
    public function create(Business $entity): Business;
    public function findById(int $id): ?Business;
    public function findPublishedBySlug(string $slug): ?Business;
    /** @return BusinessHours[] */
    public function findHours(int $businessId): array;
}
