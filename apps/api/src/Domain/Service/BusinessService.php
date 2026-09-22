<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Business, BusinessHours, BusinessMember, OrderStatus, PaymentMethod};
use App\Domain\Repository\{BusinessManagementRepositoryInterface, ObjectStorageInterface, OrderStatusRepositoryInterface, PaymentMethodRepositoryInterface};
use DomainException;

final readonly class BusinessService
{
    private const CATEGORIES = ['restaurant', 'cafe', 'beauty_salon', 'professional_services', 'store', 'repair_services', 'other'];

    public function __construct(
        private BusinessManagementRepositoryInterface $businesses,
        private BusinessMemberGuard $guard,
        private ObjectStorageInterface $storage,
        private ?PaymentMethodRepositoryInterface $paymentMethods = null,
        private ?OrderStatusRepositoryInterface $orderStatuses = null,
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
        if ($this->paymentMethods !== null) {
            $method = new PaymentMethod();
            $method->businessId = (int) $business->id;
            $method->name = 'Efectivo';
            $this->paymentMethods->create($method);
        }
        if ($this->orderStatuses !== null) {
            foreach (OrderStatus::defaults() as $defaultData) {
                $status = new OrderStatus();
                $status->businessId = (int) $business->id;
                $status->name = $defaultData['name'];
                $status->color = $defaultData['color'];
                $status->isTerminal = $defaultData['is_terminal'];
                $status->isDefault = $defaultData['is_default'];
                $status->position = $defaultData['position'];
                $this->orderStatuses->create($status);
            }
        }
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
            if ($number !== '' && !PhoneNumber::isValid($number)) {
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
        foreach (['pickup_enabled' => 'pickupEnabled', 'delivery_enabled' => 'deliveryEnabled', 'dine_in_enabled' => 'dineInEnabled'] as $field => $property) {
            if (array_key_exists($field, $input)) {
                $business->{$property} = is_string($input[$field]) ? filter_var($input[$field], FILTER_VALIDATE_BOOLEAN) : (bool) $input[$field];
            }
        }
        foreach (['pickup_fee' => 'pickupFee', 'delivery_fee' => 'deliveryFee', 'dine_in_fee' => 'dineInFee'] as $field => $property) {
            if (array_key_exists($field, $input)) {
                $fee = $input[$field];
                if ($fee === null || $fee === '') $business->{$property} = null;
                elseif (!is_numeric($fee) || (float) $fee < 0) throw new DomainException('Invalid fulfillment fee.');
                else $business->{$property} = number_format((float) $fee, 2, '.', '');
            }
        }
        if (!$business->pickupEnabled && !$business->deliveryEnabled && !$business->dineInEnabled) {
            throw new DomainException('At least one fulfillment method must be enabled.');
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
        if ($published && !$business->pickupEnabled && !$business->deliveryEnabled && !$business->dineInEnabled) {
            throw new DomainException('At least one fulfillment method must be enabled.');
        }
        if ($published && $this->paymentMethods !== null && $this->paymentMethods->findByBusinessId($businessId) === []) {
            throw new DomainException('At least one payment method must be configured.');
        }
        $business->isPublished = $published;
        return $this->businesses->update($business);
    }

    /** @return PaymentMethod[] */
    public function listPaymentMethods(int $userId, int $businessId): array
    {
        $this->guard->assertOwner($userId, $businessId);
        return $this->paymentMethods?->findByBusinessId($businessId) ?? [];
    }
    public function addPaymentMethod(int $userId, int $businessId, string $name): PaymentMethod
    {
        $this->guard->assertOwner($userId, $businessId);
        $name = trim($name);
        if ($name === '' || $this->paymentMethods === null) throw new DomainException('Payment method name is required.');
        $method = new PaymentMethod(); $method->businessId = $businessId; $method->name = $name; $method->position = count($this->paymentMethods->findByBusinessId($businessId));
        return $this->paymentMethods->create($method);
    }
    public function updatePaymentMethod(int $userId, int $businessId, int $id, array $input): PaymentMethod
    {
        $this->guard->assertOwner($userId, $businessId);
        $method = $this->findPaymentMethod($businessId, $id);
        if ($method === null || $this->paymentMethods === null) throw new DomainException('Payment method not found.');
        if (array_key_exists('name', $input)) { $name = trim((string) $input['name']); if ($name === '') throw new DomainException('Payment method name is required.'); $method->name = $name; }
        if (array_key_exists('position', $input)) $method->position = max(0, (int) $input['position']);
        return $this->paymentMethods->update($method);
    }
    public function deletePaymentMethod(int $userId, int $businessId, int $id): void
    {
        $this->guard->assertOwner($userId, $businessId);
        if ($this->paymentMethods === null) throw new DomainException('Payment method not found.');
        $methods = $this->paymentMethods->findByBusinessId($businessId);
        $method = $this->findPaymentMethod($businessId, $id);
        if ($method === null) throw new DomainException('Payment method not found.');
        if (count($methods) < 2) throw new DomainException('At least one payment method must remain.');
        $this->paymentMethods->delete($method);
    }
    private function findPaymentMethod(int $businessId, int $id): ?PaymentMethod
    {
        foreach ($this->paymentMethods?->findByBusinessId($businessId) ?? [] as $method) if ((int) $method->id === $id) return $method;
        return null;
    }

    /** @return OrderStatus[] */
    public function listOrderStatuses(int $userId, int $businessId): array
    {
        $this->guard->assertOwner($userId, $businessId);
        return $this->orderStatuses?->findByBusinessId($businessId) ?? [];
    }

    public function addOrderStatus(int $userId, int $businessId, array $input): OrderStatus
    {
        $this->guard->assertOwner($userId, $businessId);
        $this->requireOrderStatuses();
        $name = trim((string) ($input['name'] ?? ''));
        $color = (string) ($input['color'] ?? '');
        if ($name === '') throw new DomainException('Order status name is required.');
        $this->validateStatusColor($color);
        $status = new OrderStatus();
        $status->businessId = $businessId;
        $status->name = $name;
        $status->color = $color;
        $status->isTerminal = (bool) ($input['is_terminal'] ?? false);
        $status->isDefault = false;
        $status->position = count($this->orderStatuses->findByBusinessId($businessId));
        return $this->orderStatuses->create($status);
    }

    public function updateOrderStatus(int $userId, int $businessId, int $id, array $input): OrderStatus
    {
        $this->guard->assertOwner($userId, $businessId);
        $this->requireOrderStatuses();
        $status = $this->findOrderStatus($businessId, $id);
        if ($status === null) throw new DomainException('Order status not found.');
        if (array_key_exists('name', $input)) {
            $status->name = trim((string) $input['name']);
            if ($status->name === '') throw new DomainException('Order status name is required.');
        }
        if (array_key_exists('color', $input)) {
            $this->validateStatusColor((string) $input['color']);
            $status->color = (string) $input['color'];
        }
        if (array_key_exists('is_terminal', $input)) $status->isTerminal = (bool) $input['is_terminal'];
        if (array_key_exists('position', $input)) $status->position = max(0, (int) $input['position']);
        return $this->orderStatuses->update($status);
    }

    public function reorderOrderStatuses(int $userId, int $businessId, array $positions): array
    {
        $this->guard->assertOwner($userId, $businessId);
        $this->requireOrderStatuses();
        $statuses = $this->orderStatuses->findByBusinessId($businessId);
        foreach ($statuses as $status) {
            if (array_key_exists((string) $status->id, $positions)) {
                $status->position = max(0, (int) $positions[(string) $status->id]);
                $this->orderStatuses->update($status);
            }
        }
        return $this->orderStatuses->findByBusinessId($businessId);
    }

    public function setDefaultOrderStatus(int $userId, int $businessId, int $id): OrderStatus
    {
        $this->guard->assertOwner($userId, $businessId);
        $this->requireOrderStatuses();
        $status = $this->findOrderStatus($businessId, $id);
        if ($status === null) throw new DomainException('Order status not found.');
        foreach ($this->orderStatuses->findByBusinessId($businessId) as $current) {
            if ($current->isDefault && $current->id !== $status->id) {
                $current->isDefault = false;
                $this->orderStatuses->update($current);
            }
        }
        $status->isDefault = true;
        return $this->orderStatuses->update($status);
    }

    public function deleteOrderStatus(int $userId, int $businessId, int $id): void
    {
        $this->guard->assertOwner($userId, $businessId);
        $this->requireOrderStatuses();
        $statuses = $this->orderStatuses->findByBusinessId($businessId);
        $status = $this->findOrderStatus($businessId, $id);
        if ($status === null) throw new DomainException('Order status not found.');
        if (count($statuses) < 2) throw new DomainException('At least one order status must remain.');
        if ($status->isDefault) throw new DomainException('Set a different default status first.');
        if ($this->orderStatuses->existsOrderWithStatus($id)) throw new DomainException('Order status is assigned to an order.');
        $this->orderStatuses->delete($status);
    }

    private function findOrderStatus(int $businessId, int $id): ?OrderStatus
    {
        foreach ($this->orderStatuses?->findByBusinessId($businessId) ?? [] as $status) if ((int) $status->id === $id) return $status;
        return null;
    }
    private function requireOrderStatuses(): void
    {
        if ($this->orderStatuses === null) throw new DomainException('Order statuses are unavailable.');
    }
    private function validateStatusColor(string $color): void
    {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) throw new DomainException('Invalid order status color.');
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
