<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class BusinessDirectory extends Migration
{
    public function up(): void
    {
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN category TEXT NULL');
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN location TEXT NULL');
    }

    public function down(): void
    {
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS location');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS category');
    }
}
