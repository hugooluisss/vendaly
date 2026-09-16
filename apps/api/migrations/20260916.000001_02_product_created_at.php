<?php
declare(strict_types=1);
use Cycle\Migrations\Migration;
final class ProductCreatedAt extends Migration
{
    public function up(): void { $this->database()->execute('ALTER TABLE products ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP'); }
    public function down(): void { $this->database()->execute('ALTER TABLE products DROP COLUMN IF EXISTS created_at'); }
}
