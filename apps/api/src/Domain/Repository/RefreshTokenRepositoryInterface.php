<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function create(RefreshToken $entity): RefreshToken;
    public function findByHash(string $hash): ?RefreshToken;
}
