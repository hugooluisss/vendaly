<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class ProductOptions extends Migration
{
    public function up(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS product_options (id BIGSERIAL PRIMARY KEY, product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE, name VARCHAR(255) NOT NULL, selection_type VARCHAR(16) NOT NULL, required BOOLEAN NOT NULL DEFAULT FALSE, position INTEGER NOT NULL DEFAULT 0, CHECK (selection_type IN ('single', 'multiple')))");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS product_option_values (id BIGSERIAL PRIMARY KEY, product_option_id BIGINT NOT NULL REFERENCES product_options(id) ON DELETE CASCADE, name VARCHAR(255) NOT NULL, price_delta NUMERIC(12,2) NOT NULL DEFAULT 0, position INTEGER NOT NULL DEFAULT 0)");
        $this->database()->execute('CREATE TABLE IF NOT EXISTS order_item_options (id BIGSERIAL PRIMARY KEY, order_item_id BIGINT NOT NULL REFERENCES order_items(id) ON DELETE CASCADE, product_option_value_id BIGINT NULL REFERENCES product_option_values(id) ON DELETE SET NULL, option_name VARCHAR(255) NOT NULL, value_name VARCHAR(255) NOT NULL, price_delta_snapshot NUMERIC(12,2) NOT NULL DEFAULT 0)');
    }

    public function down(): void
    {
        $this->database()->execute('DROP TABLE IF EXISTS order_item_options');
        $this->database()->execute('DROP TABLE IF EXISTS product_option_values');
        $this->database()->execute('DROP TABLE IF EXISTS product_options');
    }
}
