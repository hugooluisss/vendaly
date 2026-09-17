<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class BusinessGeolocation extends Migration
{
    public function up(): void
    {
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL');
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL');
    }

    public function down(): void
    {
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS longitude');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS latitude');
    }
}
