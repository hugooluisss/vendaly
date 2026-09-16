<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'business_members')]
class BusinessMember
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('integer', name: 'user_id')]
    public int $userId = 0;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
