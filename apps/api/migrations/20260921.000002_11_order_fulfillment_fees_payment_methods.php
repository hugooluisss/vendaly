<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class OrderFulfillmentFeesPaymentMethods extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS pickup_fee DECIMAL NULL');
        $db->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL NULL');
        $db->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS dine_in_fee DECIMAL NULL');
        $db->execute('CREATE TABLE IF NOT EXISTS payment_methods (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, name VARCHAR(255) NOT NULL, position INTEGER NOT NULL DEFAULT 0)');
        $db->execute("INSERT INTO payment_methods (business_id, name, position) SELECT b.id, 'Efectivo', 0 FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM payment_methods p WHERE p.business_id = b.id)");
        $db->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS fulfillment_fee_snapshot DECIMAL NULL');
        $db->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method_id BIGINT NULL REFERENCES payment_methods(id) ON DELETE SET NULL');
        $db->execute('ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method_snapshot VARCHAR(255) NULL');
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE orders DROP COLUMN IF EXISTS payment_method_snapshot');
        $db->execute('ALTER TABLE orders DROP COLUMN IF EXISTS payment_method_id');
        $db->execute('ALTER TABLE orders DROP COLUMN IF EXISTS fulfillment_fee_snapshot');
        $db->execute('DROP TABLE IF EXISTS payment_methods');
        $db->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS dine_in_fee');
        $db->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS delivery_fee');
        $db->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS pickup_fee');
    }
}
