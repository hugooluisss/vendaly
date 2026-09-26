<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class SoftDeleteCatalogEntities extends Migration
{
    public function up(): void
    {
        $this->database()->execute('ALTER TABLE categories ADD COLUMN deleted_at TIMESTAMP NULL');
        $this->database()->execute('ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL');
    }

    public function down(): void
    {
        $this->database()->execute('ALTER TABLE products DROP COLUMN IF EXISTS deleted_at');
        $this->database()->execute('ALTER TABLE categories DROP COLUMN IF EXISTS deleted_at');
    }
}
