<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class CustomerWallet extends Migration
{
    public function up(): void
    {
        $db = $this->database();
        $db->execute("ALTER TABLE businesses ADD COLUMN wallet_enabled BOOLEAN NOT NULL DEFAULT FALSE");
        $db->execute("ALTER TABLE products ADD COLUMN wallet_amount DECIMAL(12,2) NULL");
        $db->execute("ALTER TABLE order_statuses ADD COLUMN reverses_wallet BOOLEAN NOT NULL DEFAULT FALSE");
        $db->execute("UPDATE order_statuses SET reverses_wallet = TRUE WHERE LOWER(name) IN ('cancelado', 'cancelada', 'cancelled', 'canceled')");
        $db->execute("CREATE TABLE wallet_transactions (id BIGINT AUTO_INCREMENT PRIMARY KEY, customer_id BIGINT NOT NULL, order_id BIGINT NULL, type VARCHAR(32) NOT NULL, amount DECIMAL(12,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX wallet_transactions_customer_idx (customer_id), CONSTRAINT wallet_transactions_customer_fk FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE, CONSTRAINT wallet_transactions_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL)");
    }

    public function down(): void
    {
        $db = $this->database();
        $db->execute('DROP TABLE wallet_transactions');
        $db->execute('ALTER TABLE order_statuses DROP COLUMN reverses_wallet');
        $db->execute('ALTER TABLE products DROP COLUMN wallet_amount');
        $db->execute('ALTER TABLE businesses DROP COLUMN wallet_enabled');
    }
}
