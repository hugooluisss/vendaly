<?php

declare(strict_types=1);

use Cycle\Migrations\Migration;

final class BusinessSocialLinks extends Migration
{
    public function up(): void
    {
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN facebook_url VARCHAR(2048) NULL');
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN instagram_url VARCHAR(2048) NULL');
        $this->database()->execute('ALTER TABLE businesses ADD COLUMN website_url VARCHAR(2048) NULL');
    }

    public function down(): void
    {
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS facebook_url');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS instagram_url');
        $this->database()->execute('ALTER TABLE businesses DROP COLUMN IF EXISTS website_url');
    }
}
