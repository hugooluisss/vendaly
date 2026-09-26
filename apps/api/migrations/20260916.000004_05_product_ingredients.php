<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class ProductIngredients extends Migration
{
    public function up(): void
    {
        $this->database()->execute('CREATE TABLE IF NOT EXISTS product_ingredients (id BIGINT AUTO_INCREMENT PRIMARY KEY, product_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, position INTEGER NOT NULL DEFAULT 0, CONSTRAINT product_ingredients_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)');
    }

    public function down(): void
    {
        $this->database()->execute('DROP TABLE IF EXISTS product_ingredients');
    }
}
