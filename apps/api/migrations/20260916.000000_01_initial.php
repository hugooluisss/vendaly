<?php
declare(strict_types=1);

use Cycle\Migrations\Migration;

final class InitialSchema extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (id BIGINT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS businesses (id BIGINT AUTO_INCREMENT PRIMARY KEY, owner_user_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, logo_url VARCHAR(2048), whatsapp_number VARCHAR(32), description TEXT, is_published BOOLEAN NOT NULL DEFAULT FALSE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT businesses_owner_user_fk FOREIGN KEY (owner_user_id) REFERENCES users(id)) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS business_members (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, user_id BIGINT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE (business_id, user_id), CONSTRAINT business_members_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE, CONSTRAINT business_members_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS business_hours (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, day_of_week SMALLINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6), opens_at TIME, closes_at TIME, is_closed BOOLEAN NOT NULL DEFAULT FALSE, UNIQUE (business_id, day_of_week), CONSTRAINT business_hours_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS categories (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, position INTEGER NOT NULL DEFAULT 0, CONSTRAINT categories_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS products (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, category_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT, price NUMERIC(12,2), is_active BOOLEAN NOT NULL DEFAULT TRUE, position INTEGER NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT products_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE, CONSTRAINT products_category_fk FOREIGN KEY (category_id) REFERENCES categories(id));
CREATE TABLE IF NOT EXISTS product_images (id BIGINT AUTO_INCREMENT PRIMARY KEY, product_id BIGINT NOT NULL UNIQUE, url VARCHAR(2048) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT product_images_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS orders (id BIGINT AUTO_INCREMENT PRIMARY KEY, business_id BIGINT NOT NULL, customer_note TEXT, total NUMERIC(12,2), created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT orders_business_fk FOREIGN KEY (business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS order_items (id BIGINT AUTO_INCREMENT PRIMARY KEY, order_id BIGINT NOT NULL, product_id BIGINT NOT NULL, product_name_snapshot VARCHAR(255) NOT NULL, unit_price_snapshot NUMERIC(12,2), quantity INTEGER NOT NULL CHECK (quantity > 0), note TEXT, CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, CONSTRAINT order_items_product_fk FOREIGN KEY (product_id) REFERENCES products(id));
SQL;
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) $this->database()->execute($statement);
    }
    public function down(): void
    {
        foreach (['order_items','orders','product_images','products','categories','business_hours','business_members','businesses','users'] as $table) $this->database()->execute('DROP TABLE IF EXISTS ' . $table);
    }
}
