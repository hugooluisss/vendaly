<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\{Column, Entity};

#[Entity(table: 'product_option_values')]
class ProductOptionValue
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'product_option_id')]
    public int $productOptionId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('decimal', default: '0')]
    public string $priceDelta = '0';
    #[Column('integer')]
    public int $position = 0;
}
