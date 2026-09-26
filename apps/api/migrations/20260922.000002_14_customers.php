<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class Customers extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute('CREATE TABLE customers (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, phone VARCHAR(32) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT customers_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE)');
        $db->execute('CREATE UNIQUE INDEX customers_business_phone_unique ON customers (business_id, phone)');
        $db->execute('ALTER TABLE orders ADD COLUMN customer_id BIGINT NULL');
        $db->execute('ALTER TABLE orders ADD CONSTRAINT orders_customer_fk FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('ALTER TABLE orders DROP COLUMN customer_id');
        $db->execute('DROP TABLE customers');
    }
}
