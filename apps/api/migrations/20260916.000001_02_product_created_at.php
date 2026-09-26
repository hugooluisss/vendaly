<?php
declare(strict_types=1);
use Cycle\Migrations\Migration;
final class ProductCreatedAt extends Migration
{
    public function up(): void {}
    public function down(): void { $this->database()->execute('ALTER TABLE products DROP COLUMN IF EXISTS created_at'); }
}
