<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'payment_methods')]
class PaymentMethod
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('integer')]
    public int $position = 0;
}
