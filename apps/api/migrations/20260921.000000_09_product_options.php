<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class ProductOptions extends Migration
{
    public function up(): void
    {
        $this->database()->execute("CREATE TABLE IF NOT EXISTS product_options (id BIGINT AUTO_INCREMENT PRIMARY KEY, product_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, selection_type VARCHAR(16) NOT NULL, required BOOLEAN NOT NULL DEFAULT FALSE, position INTEGER NOT NULL DEFAULT 0, CHECK (selection_type IN ('single', 'multiple')), CONSTRAINT product_options_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)");
        $this->database()->execute("CREATE TABLE IF NOT EXISTS product_option_values (id BIGINT AUTO_INCREMENT PRIMARY KEY, product_option_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, price_delta NUMERIC(12,2) NOT NULL DEFAULT 0, position INTEGER NOT NULL DEFAULT 0, CONSTRAINT product_option_values_option_fk FOREIGN KEY (product_option_id) REFERENCES product_options(id) ON DELETE CASCADE)");
        $this->database()->execute('CREATE TABLE IF NOT EXISTS order_item_options (id BIGINT AUTO_INCREMENT PRIMARY KEY, order_item_id BIGINT NOT NULL, product_option_value_id BIGINT NULL, option_name VARCHAR(255) NOT NULL, value_name VARCHAR(255) NOT NULL, price_delta_snapshot NUMERIC(12,2) NOT NULL DEFAULT 0, CONSTRAINT order_item_options_item_fk FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE, CONSTRAINT order_item_options_value_fk FOREIGN KEY (product_option_value_id) REFERENCES product_option_values(id) ON DELETE SET NULL)');
    }

    public function down(): void
    {
        $this->database()->execute('DROP TABLE IF EXISTS order_item_options');
        $this->database()->execute('DROP TABLE IF EXISTS product_option_values');
        $this->database()->execute('DROP TABLE IF EXISTS product_options');
    }
}
