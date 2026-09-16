<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\{Business, BusinessHours};
use App\Domain\Repository\BusinessManagementRepositoryInterface;

final class CycleBusinessRepository extends CycleRepository implements BusinessManagementRepositoryInterface
{
    public function create(Business $entity): Business
    {
        return $this->save($entity);
    }
    public function findById(int $id): ?Business
    {
        return $this->find(Business::class, $id);
    }
    public function findByOwnerUserId(int $userId): ?Business
    {
        return $this->orm->getRepository(Business::class)->findOne(['ownerUserId' => $userId]);
    }
    public function findByMemberUserId(int $userId): ?Business
    {
        $member = $this->orm->getRepository(\App\Domain\Entity\BusinessMember::class)->findOne(['userId' => $userId]);
        return $member === null ? null : $this->findById((int) $member->businessId);
    }
    public function findBySlug(string $slug): ?Business
    {
        return $this->orm->getRepository(Business::class)->findOne(['slug' => $slug]);
    }
    public function update(Business $business): Business
    {
        return $this->save($business);
    }
    public function createMember(\App\Domain\Entity\BusinessMember $member): \App\Domain\Entity\BusinessMember
    {
        return $this->save($member);
    }
    public function findMember(int $businessId, int $userId): ?\App\Domain\Entity\BusinessMember
    {
        return $this->orm->getRepository(\App\Domain\Entity\BusinessMember::class)->findOne(['businessId' => $businessId, 'userId' => $userId]);
    }
    public function saveHours(BusinessHours $hours): BusinessHours
    {
        $existing = $this->orm->getRepository(BusinessHours::class)->findOne(['businessId' => $hours->businessId, 'dayOfWeek' => $hours->dayOfWeek]);
        if ($existing instanceof BusinessHours) {
            $hours->id = $existing->id;
        }
        return $this->save($hours);
    }
    public function findPublishedBySlug(string $slug): ?Business
    {
        return $this->orm->getRepository(Business::class)->select()->where(['slug' => $slug, 'isPublished' => true])->fetchOne();
    }
    public function findHours(int $businessId): array
    {
        return $this->orm->getRepository(BusinessHours::class)->select()->where(['businessId' => $businessId])->orderBy('dayOfWeek')->fetchAll();
    }
}
