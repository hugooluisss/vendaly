<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class OrderStatus extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS next_order_number INTEGER NOT NULL DEFAULT 1');
        $db->execute('CREATE TABLE IF NOT EXISTS order_statuses (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, is_terminal BOOLEAN NOT NULL DEFAULT FALSE, is_default BOOLEAN NOT NULL DEFAULT FALSE, position INTEGER NOT NULL DEFAULT 0)');
        $db->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_number INTEGER NULL');
        $db->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS status_id BIGINT NULL REFERENCES order_statuses(id) ON DELETE RESTRICT');

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

        $db->execute('UPDATE orders o SET order_number = x.rn FROM (SELECT id, ROW_NUMBER() OVER (PARTITION BY business_id ORDER BY created_at, id) AS rn FROM orders) x WHERE o.id = x.id AND o.order_number IS NULL');
        $db->execute('UPDATE businesses b SET next_order_number = COALESCE((SELECT MAX(o.order_number) + 1 FROM orders o WHERE o.business_id = b.id), 1)');
        $db->execute("UPDATE orders o SET status_id = s.id FROM order_statuses s WHERE s.business_id = o.business_id AND s.is_default AND o.status_id IS NULL");
        $db->execute('ALTER TABLE orders ALTER COLUMN order_number SET NOT NULL');
        $db->execute('ALTER TABLE orders ALTER COLUMN status_id SET NOT NULL');
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE orders DROP COLUMN IF EXISTS status_id');
        $db->execute('ALTER TABLE orders DROP COLUMN IF EXISTS order_number');
        $db->execute('DROP TABLE IF EXISTS order_statuses');
        $db->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS next_order_number');
    }
}
