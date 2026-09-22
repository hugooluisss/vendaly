<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\{Column, Entity};

#[Entity(table: 'order_item_options')]
class OrderItemOption
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'order_item_id')]
    public int $orderItemId = 0;
    #[Column('integer', nullable: true, name: 'product_option_value_id')]
    public ?int $productOptionValueId = null;
    #[Column('string', name: 'option_name')]
    public string $optionName = '';
    #[Column('string', name: 'value_name')]
    public string $valueName = '';
    #[Column('decimal', name: 'price_delta_snapshot', default: '0')]
    public string $priceDeltaSnapshot = '0';
}
