<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\ForbiddenException;
use App\Domain\Repository\BusinessManagementRepositoryInterface;

final readonly class BusinessMemberGuard
{
    public function __construct(private BusinessManagementRepositoryInterface $businesses)
    {
    }

    public function assertOwner(int $userId, int $businessId): void
    {
        if ($this->businesses->findMember($businessId, $userId) === null) {
            throw new ForbiddenException('Only the business owner can manage this business.');
        }
    }
}
