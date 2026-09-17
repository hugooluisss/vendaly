<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class BusinessCoverImage extends Migration
{
    public function up(): void
    {
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN IF NOT EXISTS cover_image_url VARCHAR(2048) NULL');
    }

    public function down(): void
    {
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS cover_image_url');
    }
}
