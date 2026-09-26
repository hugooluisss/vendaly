<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class HoursAndStatusDefaults extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        foreach ([
            [0, 'NULL', 'NULL', 'TRUE'],
            [1, "'09:00:00'", "'20:00:00'", 'FALSE'],
            [2, "'09:00:00'", "'20:00:00'", 'FALSE'],
            [3, "'09:00:00'", "'20:00:00'", 'FALSE'],
            [4, "'09:00:00'", "'20:00:00'", 'FALSE'],
            [5, "'09:00:00'", "'20:00:00'", 'FALSE'],
            [6, 'NULL', 'NULL', 'TRUE'],
        ] as [$day, $opens, $closes, $closed]) {
            $db->execute(
                "INSERT INTO business_hours (business_id, day_of_week, opens_at, closes_at, is_closed) SELECT b.id, {$day}, {$opens}, {$closes}, {$closed} FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM business_hours h WHERE h.business_id = b.id)",
            );
        }

        foreach ([
            'Creado' => 'recibido',
            'Elaborando' => 'preparando',
            'Entregado' => 'entregado',
            'Cancelado' => 'cancelado',
        ] as $oldName => $newName) {
            $db->execute('UPDATE order_statuses SET name = ? WHERE name = ?', [$newName, $oldName]);
        }

        foreach ([
            ['confirmado', '#2563EB', 'FALSE'],
            ['terminado', '#16A34A', 'TRUE'],
        ] as [$name, $color, $terminal]) {
            $db->execute(
                "INSERT INTO order_statuses (business_id, name, color, is_terminal, is_default, position) SELECT b.id, ?, ?, {$terminal}, FALSE, (SELECT COALESCE(MAX(os2.position), -1) + 1 FROM order_statuses os2 WHERE os2.business_id = b.id) FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM order_statuses os WHERE os.business_id = b.id AND os.name = ?)",
                [$name, $color, $name],
            );
        }
    }

    public function down(): void
    {
        // The migration backfills owner-editable data, so it is intentionally irreversible.
    }
}
