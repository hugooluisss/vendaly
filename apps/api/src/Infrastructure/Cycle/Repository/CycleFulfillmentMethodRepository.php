<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\FulfillmentMethod;
use App\Domain\Repository\FulfillmentMethodRepositoryInterface;

final class CycleFulfillmentMethodRepository extends CycleRepository implements FulfillmentMethodRepositoryInterface
{
    public function findByBusinessId(int $businessId): array
    {
        return $this->orm->getRepository(FulfillmentMethod::class)->select()->where(['businessId' => $businessId])->orderBy('position')->fetchAll();
    }
    public function create(FulfillmentMethod $method): FulfillmentMethod { return $this->save($method); }
    public function update(FulfillmentMethod $method): FulfillmentMethod { return $this->save($method); }
    public function delete(FulfillmentMethod $method): void { $this->deleteEntity($method); }
}
