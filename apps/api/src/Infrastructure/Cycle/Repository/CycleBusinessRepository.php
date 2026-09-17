<?php

declare(strict_types=1);

namespace App\Infrastructure\Cycle\Repository;

use App\Domain\Entity\{Business, BusinessHours};
use App\Domain\Repository\BusinessManagementRepositoryInterface;
use Cycle\Database\Injection\Fragment;

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
    public function findPublishedDirectory(?string $category, ?string $location, ?float $latitude = null, ?float $longitude = null, ?string $name = null): array
    {
        $query = $this->orm->getRepository(Business::class)->select()->where(['isPublished' => true]);
        if ($category !== null && $category !== '') {
            $query = $query->where('category', '=', $category);
        }
        if ($location !== null && $location !== '') {
            $query = $query->where('location', 'ILIKE', '%' . $location . '%');
        }
        if ($name !== null && $name !== '') {
            $query = $query->where('name', 'ILIKE', '%' . $name . '%');
        }
        if ($latitude !== null && $longitude !== null) {
            $query = $query
                ->where(new Fragment('latitude IS NOT NULL AND longitude IS NOT NULL'))
                ->orderBy(new Fragment(
                    '(6371 * 2 * ASIN(SQRT(POWER(SIN((RADIANS(latitude) - RADIANS(?)) / 2), 2) + COS(RADIANS(?)) * COS(RADIANS(latitude)) * POWER(SIN((RADIANS(longitude) - RADIANS(?)) / 2), 2))))',
                    $latitude,
                    $latitude,
                    $longitude,
                ));
        }
        return $query->fetchAll();
    }
    public function findHours(int $businessId): array
    {
        return $this->orm->getRepository(BusinessHours::class)->select()->where(['businessId' => $businessId])->orderBy('dayOfWeek')->fetchAll();
    }
}
