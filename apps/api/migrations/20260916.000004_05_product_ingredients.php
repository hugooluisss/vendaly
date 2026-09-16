<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class ProductIngredients extends Migration
{
    public function up(): void
    {
        $this->database()->execute('CREATE TABLE IF NOT EXISTS product_ingredients (id BIGSERIAL PRIMARY KEY, product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE, name VARCHAR(255) NOT NULL, position INTEGER NOT NULL DEFAULT 0)');
    }

    public function down(): void
    {
        $this->database()->execute('DROP TABLE IF EXISTS product_ingredients');
    }
}
