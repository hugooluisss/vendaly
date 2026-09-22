<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class Customers extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('CREATE TABLE customers (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, phone VARCHAR(32) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT now())');
        $db->execute('CREATE UNIQUE INDEX customers_business_phone_unique ON customers (business_id, phone)');
        $db->execute('ALTER TABLE orders ADD COLUMN customer_id BIGINT NULL REFERENCES customers(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE orders DROP COLUMN customer_id');
        $db->execute('DROP TABLE customers');
    }
}
