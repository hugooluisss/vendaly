<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'fulfillment_methods')]
class FulfillmentMethod
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('decimal', nullable: true)]
    public ?string $fee = null;
    #[Column('boolean', name: 'requires_address', default: false, typecast: 'bool')]
    public bool $requiresAddress = false;
    #[Column('integer')]
    public int $position = 0;
}
