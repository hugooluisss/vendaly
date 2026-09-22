<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\PaymentMethod;
use App\Domain\Repository\PaymentMethodRepositoryInterface;

final class CyclePaymentMethodRepository extends CycleRepository implements PaymentMethodRepositoryInterface
{
    public function findByBusinessId(int $businessId): array
    {
        return $this->orm->getRepository(PaymentMethod::class)->select()->where(['businessId' => $businessId])->orderBy('position')->fetchAll();
    }
    public function create(PaymentMethod $method): PaymentMethod { return $this->save($method); }
    public function update(PaymentMethod $method): PaymentMethod { return $this->save($method); }
    public function delete(PaymentMethod $method): void { $this->deleteEntity($method); }
}
