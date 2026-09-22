<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'products')]
class Product
{
    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('integer', name: 'category_id')]
    public int $categoryId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('text', nullable: true)]
    public ?string $description = null;
    #[Column('decimal', nullable: true)]
    public ?string $price = null;
    #[Column('boolean', name: 'is_active', default: true)]
    public bool $isActive = true;
    #[Column('integer')]
    public int $position = 0;
    #[Column('datetime', name: 'created_at')]
    public string $createdAt = '';
    #[Column('datetime', name: 'deleted_at', nullable: true)]
    public ?string $deletedAt = null;
    /** @var string[] */
    public array $ingredients = [];
    /** @var ProductOption[] */
    public array $options = [];
}
