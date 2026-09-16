<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

final class CycleUserRepository extends CycleRepository implements UserRepositoryInterface
{
    public function create(User $entity): User
    {
        return $this->save($entity);
    }
    public function findById(int $id): ?User
    {
        return $this->find(User::class, $id);
    }
    public function findByEmail(string $email): ?User
    {
        return $this->orm->getRepository(User::class)->findOne(['email' => $email]);
    }
}
