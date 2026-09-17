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
    #[Column('text', nullable: true, name: 'customer_note')]
    public ?string $customerNote = null;
    #[Column('decimal', nullable: true)]
    public ?string $total = null;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
