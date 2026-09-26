<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class OrderStatus extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE businesses ADD COLUMN next_order_number INTEGER NOT NULL DEFAULT 1');
        $db->execute('CREATE TABLE IF NOT EXISTS order_statuses (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, is_terminal BOOLEAN NOT NULL DEFAULT FALSE, is_default BOOLEAN NOT NULL DEFAULT FALSE, position INTEGER NOT NULL DEFAULT 0, CONSTRAINT order_statuses_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE)');
        $db->execute('ALTER TABLE orders ADD COLUMN order_number INTEGER NULL');
        $db->execute('ALTER TABLE orders ADD COLUMN status_id BIGINT NULL');
        $db->execute('ALTER TABLE orders ADD CONSTRAINT orders_status_fk FOREIGN KEY (status_id) REFERENCES order_statuses(id) ON DELETE RESTRICT');

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

        $db->execute('UPDATE orders o JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY business_id ORDER BY created_at, id) AS rn FROM orders) x ON o.id = x.id SET o.order_number = x.rn WHERE o.order_number IS NULL');
        $db->execute('UPDATE businesses b SET next_order_number = COALESCE((SELECT MAX(o.order_number) + 1 FROM orders o WHERE o.business_id = b.id), 1)');
        $db->execute('UPDATE orders o JOIN order_statuses s ON s.business_id = o.business_id AND s.is_default SET o.status_id = s.id WHERE o.status_id IS NULL');
        $db->execute('ALTER TABLE orders MODIFY COLUMN order_number INTEGER NOT NULL');
        $db->execute('ALTER TABLE orders MODIFY COLUMN status_id BIGINT NOT NULL');
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
