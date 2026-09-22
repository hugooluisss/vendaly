<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'orders')]
class Order
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('integer', nullable: true, name: 'customer_id')]
    public ?int $customerId = null;
    #[Column('text', nullable: true, name: 'customer_note')]
    public ?string $customerNote = null;
    #[Column('decimal', nullable: true)]
    public ?string $total = null;
    #[Column('decimal', nullable: true, name: 'fulfillment_fee_snapshot')]
    public ?string $fulfillmentFeeSnapshot = null;
    #[Column('integer', nullable: true, name: 'payment_method_id')]
    public ?int $paymentMethodId = null;
    #[Column('integer', name: 'order_number')]
    public ?int $orderNumber = null;
    #[Column('integer', name: 'status_id')]
    public ?int $statusId = null;
    #[Column('string', nullable: true, name: 'payment_method_snapshot')]
    public ?string $paymentMethodSnapshot = null;
    #[Column('string', nullable: true, name: 'fulfillment_type')]
    public ?string $fulfillmentType = null;
    #[Column('text', nullable: true, name: 'delivery_address')]
    public ?string $deliveryAddress = null;
    #[Column('decimal', nullable: true, name: 'delivery_latitude', typecast: 'float')]
    public ?float $deliveryLatitude = null;
    #[Column('decimal', nullable: true, name: 'delivery_longitude', typecast: 'float')]
    public ?float $deliveryLongitude = null;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
