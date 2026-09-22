<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PaymentMethod;

interface PaymentMethodRepositoryInterface
{
    /** @return PaymentMethod[] */
    public function findByBusinessId(int $businessId): array;
    public function create(PaymentMethod $method): PaymentMethod;
    public function update(PaymentMethod $method): PaymentMethod;
    public function delete(PaymentMethod $method): void;
}
