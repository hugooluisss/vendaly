<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\{Business, Order, OrderItem, OrderItemOption, OrderStatus, PaymentMethod, Product};
use App\Domain\Repository\{BusinessRepositoryInterface, CustomerRepositoryInterface, OrderRepositoryInterface, OrderStatusRepositoryInterface, PaymentMethodRepositoryInterface, ProductOptionRepositoryInterface, ProductRepositoryInterface};
use DomainException;

final readonly class OrderService
{
    public function __construct(
        private BusinessRepositoryInterface $businesses,
        private ProductRepositoryInterface $products,
        private OrderRepositoryInterface $orders,
        private ?BusinessMemberGuard $guard = null,
        private ?ProductOptionRepositoryInterface $options = null,
        private ?PaymentMethodRepositoryInterface $paymentMethods = null,
        private ?OrderStatusRepositoryInterface $orderStatuses = null,
        private ?CustomerRepositoryInterface $customers = null,
    ) {}

    /** @return list<array{order: Order, items: OrderItem[]}> */
    public function listForBusiness(int $userId, int $businessId, ?string $from = null, ?string $to = null): array
    {
        $this->guard?->assertOwner($userId, $businessId);
        $entries = $this->orders->findByBusinessId($businessId, $from, $to);
        $ids = array_values(array_unique(array_filter(array_map(static fn(array $entry): ?int => $entry['order']->customerId, $entries))));
        $customers = $this->customers?->findByIdsForBusiness($businessId, $ids) ?? [];
        $statuses = [];
        foreach ($this->orderStatuses?->findByBusinessId($businessId) ?? [] as $status) $statuses[(int) $status->id] = $status;
        return array_map(static function (array $entry) use ($statuses, $customers): array {
            $entry['status'] = $statuses[(int) $entry['order']->statusId] ?? null;
            $entry['customer_phone'] = $customers[(int) $entry['order']->customerId]->phone ?? null;
            return $entry;
        }, $entries);
    }

    /** @param array{phone?:string,items?: list<array{product_id:int,quantity:int,note?:string}>,customer_note?:string,fulfillment_type?:string,delivery_address?:string,delivery_latitude?:float|string,delivery_longitude?:float|string} $input */
    public function create(string $slug, array $input): array
    {
        $business = $this->businesses->findPublishedBySlug($slug);
        $this->validateBusiness($business);
        $inputItems = $input['items'] ?? [];
        if ($inputItems === []) {
            throw new DomainException('Order must contain at least one item.');
        }
        $phone = $input['phone'] ?? null;
        if (!is_string($phone) || !PhoneNumber::isValid($phone)) {
            throw new DomainException('Valid phone number is required.');
        }
        $this->validateFulfillment($business, $input);
        $paymentMethod = $this->validatePaymentMethod($business, $input);
        $status = $this->defaultStatus((int) $business->id);

        $products = $this->loadProducts($inputItems, (int) $business->id);
        $optionGroups = $this->options?->findByProductIds(array_keys($products)) ?? [];
        [$order, $orderItems, $itemOptions] = $this->buildOrder($business, $input, $inputItems, $products, $optionGroups, $paymentMethod);
        $order->statusId = $status?->id;
        $this->orders->createWithItems($order, $orderItems, $itemOptions, $phone);

        return ['order' => $order, 'items' => $orderItems, 'whatsapp_number' => $business->whatsappNumber];
    }

    public function changeStatus(int $userId, int $businessId, int $orderId, int $statusId): Order
    {
        $this->guard?->assertOwner($userId, $businessId);
        if ($this->orderStatuses === null) throw new DomainException('Order statuses are unavailable.');
        $order = $this->orders->findById($orderId);
        if ($order === null || (int) $order->businessId !== $businessId) throw new DomainException('Order not found.');
        $status = null;
        foreach ($this->orderStatuses->findByBusinessId($businessId) as $candidate) if ((int) $candidate->id === $statusId) $status = $candidate;
        if (!$status instanceof OrderStatus) throw new DomainException('Invalid order status.');
        $order->statusId = $status->id;
        return $this->orders->create($order);
    }

    private function defaultStatus(int $businessId): ?OrderStatus
    {
        foreach ($this->orderStatuses?->findByBusinessId($businessId) ?? [] as $status) if ($status->isDefault) return $status;
        return null;
    }

    private function validateBusiness(?Business $business): void
    {
        if ($business === null) {
            throw new DomainException('Catalog not found.');
        }
        if ($business->whatsappNumber === null || trim($business->whatsappNumber) === '') {
            throw new DomainException('Business cannot currently receive orders.');
        }
    }

    private function loadProducts(array $inputItems, int $businessId): array
    {
        $ids = array_map(static fn(array $item): int => (int) ($item['product_id'] ?? 0), $inputItems);
        $products = [];
        foreach ($this->products->findActiveByIdsForBusiness(array_values(array_unique($ids)), $businessId) as $product) {
            $products[$product->id] = $product;
        }
        return $products;
    }

    private function buildOrder(Business $business, array $input, array $inputItems, array $products, array $optionGroups, ?PaymentMethod $paymentMethod): array
    {
        $order = new Order();
        $order->businessId = (int) $business->id;
        $order->customerNote = $input['customer_note'] ?? null;
        $order->fulfillmentType = $input['fulfillment_type'];
        $order->deliveryAddress = isset($input['delivery_address']) ? trim((string) $input['delivery_address']) ?: null : null;
        $order->deliveryLatitude = isset($input['delivery_latitude']) && $input['delivery_latitude'] !== '' ? (float) $input['delivery_latitude'] : null;
        $order->deliveryLongitude = isset($input['delivery_longitude']) && $input['delivery_longitude'] !== '' ? (float) $input['delivery_longitude'] : null;
        $order->paymentMethodId = $paymentMethod?->id;
        $order->paymentMethodSnapshot = $paymentMethod?->name;
        $fees = ['pickup' => $business->pickupFee, 'delivery' => $business->deliveryFee, 'dine_in' => $business->dineInFee];
        $order->fulfillmentFeeSnapshot = $fees[$order->fulfillmentType] ?? null;
        $order->createdAt = date(DATE_ATOM);
        $orderItems = [];
        $totalCents = 0;
        $hasPrice = false;
        $itemOptions = [];
        foreach ($inputItems as $index => $inputItem) {
            $quantity = (int) ($inputItem['quantity'] ?? 0);
            if ($quantity < 1) {
                throw new DomainException('Quantity must be positive.');
            }
            $product = $products[(int) ($inputItem['product_id'] ?? 0)] ?? null;
            if (!$product instanceof Product) {
                throw new DomainException('Product is not available.');
            }
            $selectedIds = array_map('intval', $inputItem['option_value_ids'] ?? $inputItem['selected_option_value_ids'] ?? []);
            $selected = $this->validateOptions($product, $selectedIds, $optionGroups[$product->id] ?? []);
            $orderItems[] = $this->buildItem($product, $quantity, $inputItem['note'] ?? null, $selected);
            $itemOptions[$index] = array_map(static function (array $selection): OrderItemOption {
                $snapshot = new OrderItemOption();
                $snapshot->productOptionValueId = $selection['value']->id;
                $snapshot->optionName = $selection['option']->name;
                $snapshot->valueName = $selection['value']->name;
                $snapshot->priceDeltaSnapshot = $selection['value']->priceDelta;
                return $snapshot;
            }, $selected);
            $orderItems[$index]->options = $itemOptions[$index];
            if ($product->price !== null) {
                $hasPrice = true;
                $unitCents = (int) round((float) $product->price * 100);
                foreach ($selected as $selection) {
                    $unitCents += (int) round((float) $selection['value']->priceDelta * 100);
                }
                $totalCents += $unitCents * $quantity;
            }
        }
        $feeCents = $order->fulfillmentFeeSnapshot === null ? 0 : (int) round((float) $order->fulfillmentFeeSnapshot * 100);
        $order->total = $hasPrice ? number_format(($totalCents + $feeCents) / 100, 2, '.', '') : null;
        return [$order, $orderItems, $itemOptions];
    }

    private function validatePaymentMethod(Business $business, array $input): ?PaymentMethod
    {
        if ($this->paymentMethods === null) return null;
        $id = $input['payment_method_id'] ?? null;
        if ($id === null || !is_numeric($id)) throw new DomainException('Payment method is required.');
        foreach ($this->paymentMethods->findByBusinessId((int) $business->id) as $method) if ((int) $method->id === (int) $id) return $method;
        throw new DomainException('Invalid payment method.');
    }

    private function validateFulfillment(Business $business, array $input): string
    {
        $type = $input['fulfillment_type'] ?? null;
        $enabled = ['pickup' => $business->pickupEnabled, 'delivery' => $business->deliveryEnabled, 'dine_in' => $business->dineInEnabled];
        if (!is_string($type) || !array_key_exists($type, $enabled) || !$enabled[$type]) {
            throw new DomainException('Invalid or unavailable fulfillment method.');
        }
        if ($type !== 'delivery') return $type;
        $address = trim((string) ($input['delivery_address'] ?? ''));
        $hasLatitude = array_key_exists('delivery_latitude', $input) && $input['delivery_latitude'] !== '' && $input['delivery_latitude'] !== null;
        $hasLongitude = array_key_exists('delivery_longitude', $input) && $input['delivery_longitude'] !== '' && $input['delivery_longitude'] !== null;
        if ($hasLatitude xor $hasLongitude || (!$hasLatitude && !$hasLongitude && $address === '')) {
            throw new DomainException('Delivery requires an address or complete coordinates.');
        }
        if (($hasLatitude && (!is_numeric($input['delivery_latitude']) || (float) $input['delivery_latitude'] < -90 || (float) $input['delivery_latitude'] > 90))
            || ($hasLongitude && (!is_numeric($input['delivery_longitude']) || (float) $input['delivery_longitude'] < -180 || (float) $input['delivery_longitude'] > 180))) {
            throw new DomainException('Invalid delivery coordinates.');
        }
        return $type;
    }

    private function buildItem(Product $product, int $quantity, ?string $note, array $selected): OrderItem
    {
        $item = new OrderItem();
        $item->productId = (int) $product->id;
        $item->productNameSnapshot = $product->name;
        if ($product->price !== null) {
            $unitCents = (int) round((float) $product->price * 100);
            foreach ($selected as $selection) {
                $unitCents += (int) round((float) $selection['value']->priceDelta * 100);
            }
            $item->unitPriceSnapshot = number_format($unitCents / 100, 2, '.', '');
        }
        $item->quantity = $quantity;
        $item->note = $note;
        return $item;
    }
    private function validateOptions(Product $product, array $selectedIds, array $groups): array
    {
        if (count($selectedIds) !== count(array_unique($selectedIds))) {
            throw new DomainException('Option values must be unique.');
        }
        $selected = [];
        $seenByGroup = [];
        foreach ($groups as $group) {
            $values = array_filter($group->values, static fn($value): bool => in_array((int) $value->id, $selectedIds, true));
            $seenByGroup[$group->id] = count($values);
            if ($group->selectionType === 'single' && count($values) > 1) {
                throw new DomainException('Single-select options allow one value.');
            }
            if ($group->required && $group->selectionType === 'single' && count($values) !== 1) {
                throw new DomainException('Required option selection is missing.');
            }
            foreach ($values as $value) {
                $selected[] = ['option' => $group, 'value' => $value];
            }
        }
        $knownIds = array_map(static fn(array $selection): int => (int) $selection['value']->id, $selected);
        if (array_diff($selectedIds, $knownIds) !== []) {
            throw new DomainException('Option value is not available for this product.');
        }
        return $selected;
    }
}
