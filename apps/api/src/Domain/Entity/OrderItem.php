<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'order_items')]
class OrderItem
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'order_id')]
    public int $orderId = 0;
    #[Column('integer', name: 'product_id')]
    public int $productId = 0;
    #[Column('string', name: 'product_name_snapshot')]
    public string $productNameSnapshot = '';
    #[Column('decimal', nullable: true, name: 'unit_price_snapshot')]
    public ?string $unitPriceSnapshot = null;
    #[Column('integer')]
    public int $quantity = 1;
    #[Column('text', nullable: true)]
    public ?string $note = null;
}
