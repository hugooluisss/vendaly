<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'product_images')]
class ProductImage
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'product_id')]
    public int $productId = 0;
    #[Column('string')]
    public string $url = '';
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
}
