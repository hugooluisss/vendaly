<?php
declare(strict_types=1);

use Cycle\Migrations\Migration;

final class InitialSchema extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (id BIGSERIAL PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS businesses (id BIGSERIAL PRIMARY KEY, owner_user_id BIGINT NOT NULL REFERENCES users(id), name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, logo_url VARCHAR(2048), whatsapp_number VARCHAR(32), description TEXT, is_published BOOLEAN NOT NULL DEFAULT FALSE, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS business_members (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE (business_id, user_id));
CREATE TABLE IF NOT EXISTS business_hours (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, day_of_week SMALLINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6), opens_at TIME, closes_at TIME, is_closed BOOLEAN NOT NULL DEFAULT FALSE, UNIQUE (business_id, day_of_week));
CREATE TABLE IF NOT EXISTS categories (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, name VARCHAR(255) NOT NULL, position INTEGER NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS products (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, category_id BIGINT NOT NULL REFERENCES categories(id), name VARCHAR(255) NOT NULL, description TEXT, price NUMERIC(12,2), is_active BOOLEAN NOT NULL DEFAULT TRUE, position INTEGER NOT NULL DEFAULT 0, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS product_images (id BIGSERIAL PRIMARY KEY, product_id BIGINT NOT NULL UNIQUE REFERENCES products(id) ON DELETE CASCADE, url VARCHAR(2048) NOT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS orders (id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id), customer_note TEXT, total NUMERIC(12,2), created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS order_items (id BIGSERIAL PRIMARY KEY, order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE, product_id BIGINT NOT NULL REFERENCES products(id), product_name_snapshot VARCHAR(255) NOT NULL, unit_price_snapshot NUMERIC(12,2), quantity INTEGER NOT NULL CHECK (quantity > 0), note TEXT);
SQL;
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) $this->database()->execute($statement);
    }
    public function down(): void
    {
        foreach (['order_items','orders','product_images','products','categories','business_hours','business_members','businesses','users'] as $table) $this->database()->execute('DROP TABLE IF EXISTS ' . $table . ' CASCADE');
    }
}
