<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\FulfillmentMethod;

interface FulfillmentMethodRepositoryInterface
{
    /** @return FulfillmentMethod[] */
    public function findByBusinessId(int $businessId): array;
    public function create(FulfillmentMethod $method): FulfillmentMethod;
    public function update(FulfillmentMethod $method): FulfillmentMethod;
    public function delete(FulfillmentMethod $method): void;
}
