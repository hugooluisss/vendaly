<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\{Business, BusinessMember, BusinessHours};

interface BusinessManagementRepositoryInterface extends BusinessRepositoryInterface
{
    public function findByOwnerUserId(int $userId): ?Business;
    public function findByMemberUserId(int $userId): ?Business;
    public function findBySlug(string $slug): ?Business;
    public function update(Business $business): Business;
    public function createMember(BusinessMember $member): BusinessMember;
    public function findMember(int $businessId, int $userId): ?BusinessMember;
    public function saveHours(BusinessHours $hours): BusinessHours;
}
