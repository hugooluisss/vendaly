<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\{Column, Entity};

#[Entity(table: 'product_options')]
class ProductOption
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'product_id')]
    public int $productId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('string', name: 'selection_type')]
    public string $selectionType = 'single';
    #[Column('boolean', default: false)]
    public bool $required = false;
    #[Column('integer')]
    public int $position = 0;
    /** @var ProductOptionValue[] */
    public array $values = [];
}
