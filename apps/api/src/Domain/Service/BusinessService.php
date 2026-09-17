<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Business, BusinessHours, BusinessMember};
use App\Domain\Repository\{BusinessManagementRepositoryInterface, ObjectStorageInterface};
use DomainException;

final readonly class BusinessService
{
    private const CATEGORIES = ['restaurant', 'cafe', 'beauty_salon', 'professional_services', 'store', 'repair_services', 'other'];

    public function __construct(
        private BusinessManagementRepositoryInterface $businesses,
        private BusinessMemberGuard $guard,
        private ObjectStorageInterface $storage,
    ) {
    }

    public function create(int $userId, string $name): Business
    {
        $name = trim($name);
        if ($name === '') {
            throw new DomainException('Business name is required.');
        }
        if ($this->businesses->findByOwnerUserId($userId) !== null) {
            throw new DomainException('User already owns a business.');
        }
        $slug = $this->uniqueSlug($this->slugify($name));
        $business = new Business();
        $business->ownerUserId = $userId;
        $business->name = $name;
        $business->slug = $slug;
        $business->createdAt = date(DATE_ATOM);
        $business = $this->businesses->create($business);
        $member = new BusinessMember();
        $member->businessId = (int) $business->id;
        $member->userId = $userId;
        $member->createdAt = date(DATE_ATOM);
        $this->businesses->createMember($member);
        return $business;
    }

    public function findByOwner(int $userId): ?array
    {
        $business = $this->businesses->findByMemberUserId($userId);
        return $business === null ? null : ['business' => $business, 'hours' => $this->businesses->findHours((int) $business->id)];
    }

    public function updateProfile(int $userId, int $businessId, array $input, ?string $logo = null, string $contentType = 'application/octet-stream', ?string $coverImage = null, string $coverContentType = 'application/octet-stream'): Business
    {
        $this->guard->assertOwner($userId, $businessId);
        $business = $this->businesses->findById($businessId);
        if ($business === null) {
            throw new DomainException('Business not found.');
        }
        if (array_key_exists('name', $input)) {
            $name = trim((string) $input['name']);
            if ($name === '') {
                throw new DomainException('Business name is required.');
            }
            $business->name = $name;
        }
        if (array_key_exists('whatsapp_number', $input)) {
            $number = trim((string) $input['whatsapp_number']);
            if ($number !== '' && !preg_match('/^\+?[1-9][0-9 ()-]{6,20}$/', $number)) {
                throw new DomainException('Invalid whatsapp_number.');
            }
            $business->whatsappNumber = $number === '' ? null : $number;
        }
        if (array_key_exists('description', $input)) {
            $business->description = $input['description'] === null ? null : trim((string) $input['description']);
        }
        if (array_key_exists('category', $input)) {
            $category = $input['category'];
            if ($category !== null && (!is_string($category) || !in_array($category, self::CATEGORIES, true))) {
                throw new DomainException('Invalid category.');
            }
            $business->category = $category;
        }
        if (array_key_exists('location', $input)) {
            $business->location = $input['location'] === null ? null : trim((string) $input['location']);
        }
        if (array_key_exists('latitude', $input) || array_key_exists('longitude', $input)) {
            $latitude = $input['latitude'] ?? null;
            $longitude = $input['longitude'] ?? null;
            if (($latitude === null || $latitude === '') && ($longitude === null || $longitude === '')) {
                $business->latitude = $business->longitude = null;
            } elseif (!array_key_exists('latitude', $input) || !array_key_exists('longitude', $input)
                || !is_numeric($latitude) || !is_numeric($longitude)
                || !is_finite((float) $latitude) || !is_finite((float) $longitude)
                || (float) $latitude < -90 || (float) $latitude > 90
                || (float) $longitude < -180 || (float) $longitude > 180) {
                throw new DomainException('Invalid coordinates.');
            } else {
                $business->latitude = (float) $latitude;
                $business->longitude = (float) $longitude;
            }
        }
        if ($logo !== null) {
            $business->logoUrl = $this->storage->put('businesses/' . $businessId . '/logo', $logo, $contentType);
        }
        if ($coverImage !== null) {
            $business->coverImageUrl = $this->storage->put('businesses/' . $businessId . '/cover', $coverImage, $coverContentType);
        }
        return $this->businesses->update($business);
    }

    public function updateHours(int $userId, int $businessId, array $days): array
    {
        $this->guard->assertOwner($userId, $businessId);
        $saved = [];
        foreach ($days as $day) {
            $number = (int) ($day['day_of_week'] ?? -1);
            if ($number < 0 || $number > 6) {
                throw new DomainException('Invalid day_of_week.');
            }
            $hours = new BusinessHours();
            $hours->businessId = $businessId;
            $hours->dayOfWeek = $number;
            $hours->isClosed = (bool) ($day['is_closed'] ?? false);
            $hours->opensAt = $hours->isClosed ? null : ($day['opens_at'] ?? null);
            $hours->closesAt = $hours->isClosed ? null : ($day['closes_at'] ?? null);
            $saved[] = $this->businesses->saveHours($hours);
        }
        return $saved;
    }

    public function setPublished(int $userId, int $businessId, bool $published): Business
    {
        $this->guard->assertOwner($userId, $businessId);
        $business = $this->businesses->findById($businessId);
        if ($business === null) {
            throw new DomainException('Business not found.');
        }
        if ($published && !$business->isPublished && ($business->latitude === null || $business->longitude === null)) {
            throw new DomainException('Set a location on the map before publishing.');
        }
        $business->isPublished = $published;
        return $this->businesses->update($business);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;
        while ($this->businesses->findBySlug($slug) !== null) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-') ?: 'business';
    }
}
