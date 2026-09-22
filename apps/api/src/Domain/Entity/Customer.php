<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'customers')]
class Customer
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('string')]
    public string $phone = '';
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
