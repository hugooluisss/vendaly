<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'product_ingredients')]
class ProductIngredient
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'product_id')]
    public int $productId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('integer')]
    public int $position = 0;
}
