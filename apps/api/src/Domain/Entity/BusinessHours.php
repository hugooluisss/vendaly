<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'business_hours')]
class BusinessHours
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('integer', name: 'day_of_week')]
    public int $dayOfWeek = 0;
    #[Column('time', nullable: true, name: 'opens_at')]
    public ?string $opensAt = null;
    #[Column('time', nullable: true, name: 'closes_at')]
    public ?string $closesAt = null;
    #[Column('boolean', name: 'is_closed', default: false)]
    public bool $isClosed = false;
}
