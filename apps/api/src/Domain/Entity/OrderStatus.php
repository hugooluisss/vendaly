<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(table: 'order_statuses')]
class OrderStatus
{
    /** @return list<array{name: string, color: string, is_terminal: bool, is_default: bool, reverses_wallet: bool, position: int}> */
    public static function defaults(): array
    {
        return [
            ['name' => 'recibido', 'color' => '#EA580C', 'is_terminal' => false, 'is_default' => true, 'position' => 0],
            ['name' => 'confirmado', 'color' => '#2563EB', 'is_terminal' => false, 'is_default' => false, 'position' => 1],
            ['name' => 'preparando', 'color' => '#7C3AED', 'is_terminal' => false, 'is_default' => false, 'position' => 2],
            ['name' => 'entregado', 'color' => '#0891B2', 'is_terminal' => false, 'is_default' => false, 'position' => 3],
            ['name' => 'terminado', 'color' => '#16A34A', 'is_terminal' => true, 'is_default' => false, 'position' => 4],
            ['name' => 'cancelado', 'color' => '#DC2626', 'is_terminal' => true, 'is_default' => false, 'reverses_wallet' => true, 'position' => 5],
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
    #[Column('boolean', name: 'is_terminal', default: false, typecast: 'bool')]
    public bool $isTerminal = false;
    #[Column('boolean', name: 'is_default', default: false, typecast: 'bool')]
    public bool $isDefault = false;
    #[Column('boolean', name: 'reverses_wallet', default: false, typecast: 'bool')]
    public bool $reversesWallet = false;
    #[Column('integer')]
    public int $position = 0;
}
