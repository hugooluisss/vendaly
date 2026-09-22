<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class OrderStatusSeedRepair extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        foreach ([
            ['Creado', '#EA580C', 'FALSE', 'TRUE', 0],
            ['Elaborando', '#2563EB', 'FALSE', 'FALSE', 1],
            ['Entregado', '#16A34A', 'TRUE', 'FALSE', 2],
            ['Cancelado', '#DC2626', 'TRUE', 'FALSE', 3],
        ] as [$name, $color, $terminal, $default, $position]) {
            $db->execute(
                "INSERT INTO order_statuses (business_id, name, color, is_terminal, is_default, position) SELECT id, ?, ?, {$terminal}, {$default}, ? FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM order_statuses s WHERE s.business_id = b.id AND s.name = ?)",
                [$name, $color, $position, $name],
            );
        }
    }

    public function down(): void
    {
        $this->database()->execute("DELETE FROM order_statuses WHERE name IN ('Creado', 'Elaborando', 'Entregado', 'Cancelado')");
    }
}
