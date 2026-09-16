<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\RefreshToken;
use App\Domain\Repository\RefreshTokenRepositoryInterface;

final class CycleRefreshTokenRepository extends CycleRepository implements RefreshTokenRepositoryInterface
{
    public function create(RefreshToken $entity): RefreshToken
    {
        return $this->save($entity);
    }
    public function findByHash(string $hash): ?RefreshToken
    {
        return $this->orm->getRepository(RefreshToken::class)->findOne(['token_hash' => $hash]);
    }
}
