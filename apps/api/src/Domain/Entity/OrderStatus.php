<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'order_statuses')]
class OrderStatus
{
    /** @return list<array{name: string, color: string, is_terminal: bool, is_default: bool, position: int}> */
    public static function defaults(): array
    {
        return [
            ['name' => 'Creado', 'color' => '#EA580C', 'is_terminal' => false, 'is_default' => true, 'position' => 0],
            ['name' => 'Elaborando', 'color' => '#2563EB', 'is_terminal' => false, 'is_default' => false, 'position' => 1],
            ['name' => 'Entregado', 'color' => '#16A34A', 'is_terminal' => true, 'is_default' => false, 'position' => 2],
            ['name' => 'Cancelado', 'color' => '#DC2626', 'is_terminal' => true, 'is_default' => false, 'position' => 3],
        ];
    }

    #[Column('primary')]
    public ?int $id = null;
    #[Column('integer', name: 'business_id')]
    public int $businessId = 0;
    #[Column('string')]
    public string $name = '';
    #[Column('string')]
    public string $color = '#EA580C';
    #[Column('boolean', name: 'is_terminal', default: false)]
    public bool $isTerminal = false;
    #[Column('boolean', name: 'is_default', default: false)]
    public bool $isDefault = false;
    #[Column('integer')]
    public int $position = 0;
}
